<?php

use App\Models\User;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public ?int $status = null;
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    public ?User $selectedTenant = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => null],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount()
    {
        if (!auth()->user()->hasPermissionTo('view-tenant')) {
            abort(403);
        }
    }

    // Use a computed property instead of storing the paginator
    public function getTenantsProperty()
    {
        return User::query()
            ->with(['bookings'])
            ->role('Tenant')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('nin', 'like', "%{$this->search}%");
                });
            })
            ->when(!is_null($this->status), fn($query) => $query->where('is_active', $this->status))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'sortField', 'sortDirection');
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->selectedTenant = User::findOrFail($id);
        Flux::modal('delete-tenant')->show();
    }

    public function delete(): void
    {
        if (! $this->selectedTenant) {
            return;
        }

        if ($this->selectedTenant->bookings()->exists()) {
            session()->flash(
                'error',
                'This tenant contains bookings and cannot be deleted.'
            );

            Flux::modal('delete-tenant')->close();
            return;
        }

        $this->selectedTenant->delete();
        Flux::modal('delete-tenant')->close();

        session()->flash(
            'success',
            'Tenant deleted successfully.'
        );

        $this->reset('selectedTenant');
        $this->resetPage();
    }
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="flex items-center gap-2">
                Tenants
                <span class="text-sm font-normal text-neutral-500 dark:text-neutral-400">
                    ({{ $this->tenants->total() }})
                </span>
            </flux:heading>
            <flux:text class="mt-1">
                Manage all tenant.
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" :href="route('tenant.create')" size="sm">
                New Tenants
            </flux:button>
        </div>
    </div>

    <!-- Flash Messages -->
    <x-flash-message />
    
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Tenants</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ \App\Models\User::role('Tenant')->count() }}
                    </p>
                </div>
                <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                    <flux:icon.user-group color="blue" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Active</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ \App\Models\User::role('Tenant')->where('is_active', true)->count() }}
                    </p>
                </div>
                <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                    <flux:icon.check-circle color="green" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Inactive</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ \App\Models\User::role('Tenant')->where('is_active', false)->count() }}
                    </p>
                </div>
                <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
                    <flux:icon.x-circle color="red" />
                </div>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
        <!-- Filters -->
        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Search by name, email, phone, or nin..."
                    class="w-full"
                />
            </div>

            <div class="w-full sm:w-48">
                <flux:select wire:model.live="status">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </flux:select>
            </div>

            <div class="flex gap-2">
                <flux:button
                    variant="danger"
                    icon="x-mark"
                    wire:click="clearFilters"
                    size="sm"
                >
                    Clear Filters
                </flux:button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-800/50">
                    <tr class="text-left text-xs font-medium tracking-wider text-neutral-500 dark:text-neutral-400">
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('id')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                #
                                @if($sortField === 'id')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('name')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                Name
                                @if($sortField === 'name')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('email')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                Email
                                @if($sortField === 'email')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('phone')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                Phone Number
                                @if($sortField === 'phone')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <button wire:click="sortBy('bookings')" class="flex items-center justify-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                Bookings
                                @if($sortField === 'bookings')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900/50">
                    @forelse ($this->tenants as $tenant)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $loop->iteration }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                    <flux:icon.user-circle color="blue" />
                                </div>
                                <div>
                                    <div class="font-medium text-neutral-900 dark:text-white">
                                        {{ $tenant->name }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-700 dark:text-neutral-300">
                            {{ Str::limit($tenant->email, 30) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-700 dark:text-neutral-300">
                            {{ $tenant->phone ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                {{ number_format($tenant->bookings->count()) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tenant->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                <span class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full {{ $tenant->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <flux:dropdown>
                                <flux:button size="sm" icon="ellipsis-vertical" variant="ghost" />

                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="route('tenant.show', $tenant)">
                                        View Details
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil-square" :href="route('tenant.edit', $tenant)">
                                        Edit Tenant
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item 
                                        variant="danger" 
                                        icon="trash" 
                                        wire:click="confirmDelete({{ $tenant->id }})"
                                    >
                                        Delete Tenant
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">
                                        No tenant found matching your filters.
                                    </p>
                                    <flux:button variant="ghost" wire:click="clearFilters" size="sm">
                                        Clear filters
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="border-t border-neutral-200 px-4 py-3 dark:border-neutral-700">
            {{ $this->tenants->links() }}
        </div>
    </div>

    <!-- Delete Modal -->
    <flux:modal name="delete-tenant" class="md:w-96">
        <div class="space-y-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-full bg-red-100 p-2 dark:bg-red-900/30">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <flux:heading size="lg">
                        Delete Tenant
                    </flux:heading>
                    <flux:text class="mt-2">
                        Are you sure you want to delete
                        <strong class="text-neutral-900 dark:text-white">{{ $selectedTenant?->name }}</strong>?
                    </flux:text>
                    <div class="mt-2 rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                        <flux:text class="text-sm text-red-600 dark:text-red-400">
                            ⚠️ This action cannot be undone.
                        </flux:text>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button
                    variant="ghost"
                    x-on:click="$flux.modal('delete-tenant').close()"
                >
                    Cancel
                </flux:button>

                <flux:button
                    variant="danger"
                    icon="trash"
                    wire:click="delete"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>
                        Delete Tenant
                    </span>
                    <span wire:loading>
                        Deleting...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>