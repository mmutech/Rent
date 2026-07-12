<?php

use App\Models\Unit;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $sortField = 'title';
    public string $sortDirection = 'asc';

    public ?Unit $selectedUnit = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'sortField' => ['except' => 'title'],
        'sortDirection' => ['except' => 'asc'],
    ];

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

    public function getUnitsProperty()
    {
        return Unit::with(['compound', 'property'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('amount', 'like', "%{$this->search}%");
                });
            })
            ->when(
                $this->status !== '',
                fn ($query) => $query->where('status', $this->status)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'sortField', 'sortDirection');
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->selectedUnit = Unit::with('properties')->findOrFail($id);
        Flux::modal('delete-unit')->show();
    }

    public function delete(): void
    {
        if (! $this->selectedUnit) {
            return;
        }

        if ($this->selectedUnit->properties()->exists()) {
            session()->flash(
                'error',
                'This unit contains properties and cannot be deleted.'
            );

            Flux::modal('delete-unit')->close();
            return;
        }

        $this->selectedUnit->delete();
        Flux::modal('delete-unit')->close();

        session()->flash(
            'success',
            'Unit deleted successfully.'
        );

        $this->reset('selectedUnit');
        $this->resetPage();
    }
};
?>

<!-- Your view remains largely the same, just ensure to update the status display -->

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="flex items-center gap-2">
                Property
                <span class="text-sm font-normal text-neutral-500 dark:text-neutral-400">
                    ({{ $this->units->total() }})
                </span>
            </flux:heading>
            <flux:text class="mt-1">
                Manage all property where properties are located.
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" size="sm" :href="route('property.create')">
                New Property
            </flux:button>
        </div>
    </div>

    <!-- Flash Messages -->
    <x-flash-message />
    
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Units</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ \App\Models\Unit::count() }}
                    </p>
                </div>
                <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Available</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ \App\Models\Unit::where('status', 'Available')->count() }}
                    </p>
                </div>
                <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Reserved</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ \App\Models\Unit::where('status', 'Reserved')->count() }}
                    </p>
                </div>
                <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Occupied</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ \App\Models\Unit::where('status', 'Occupied')->count() }}
                    </p>
                </div>
                <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
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
                    placeholder="Search by title, amount..."
                    class="w-full"
                />
            </div>

            <div class="w-full sm:w-48">
                <flux:select wire:model.live="status">
                    <option value="">All Status</option>
                    <option value="Available">Available</option>
                    <option value="Reserved">Reserved</option>
                    <option value="Occupied">Occupied</option>
                    <option value="Under_Maintenance">Under Maintenance</option>
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
                    <tr class="text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('id')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                #
                                @if($sortField === 'id')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('compound_id')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                Type
                                @if($sortField === 'compound_id')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('title')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                Title
                                @if($sortField === 'title')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-center">
                            <button wire:click="sortBy('bedrooms')" class="flex items-center justify-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                Bedrooms
                                @if($sortField === 'bedrooms')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3">
                            <button wire:click="sortBy('amount')" class="flex items-center gap-1 hover:text-neutral-700 dark:hover:text-neutral-300">
                                Amount
                                @if($sortField === 'amount')
                                    <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900/50">
                    @forelse ($this->units as $unit)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500 dark:text-neutral-400">
                            {{ $loop->iteration }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-medium text-neutral-900 dark:text-white">
                                        {{ $unit->compound?->name ?? 'N/A' }}
                                    </div>
                                    @if($unit->property?->name)
                                        <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $unit->property->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-700 dark:text-neutral-300">
                            {{ Str::limit($unit->title, 30) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                {{ number_format($unit->bedrooms) }}
                                @if($unit->bedrooms > 0)
                                    <span class="text-xs text-neutral-400">Units</span>
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-700 dark:text-neutral-300">
                            {{ number_format($unit->amount) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                {{ match($unit->status) {
                                    'Available' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'Reserved' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    'Occupied' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    'Under_Maintenance' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                                } }}">
                                <span class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full 
                                    {{ match($unit->status) {
                                        'Available' => 'bg-green-400',
                                        'Reserved' => 'bg-yellow-400',
                                        'Occupied' => 'bg-blue-400',
                                        'Under_Maintenance' => 'bg-red-400',
                                        default => 'bg-gray-400',
                                    } }}">
                                </span>
                                {{ str_replace('_', ' ', $unit->status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <flux:dropdown>
                                <flux:button size="sm" icon="ellipsis-vertical" variant="ghost" />

                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="route('property.show', $unit)">
                                        View Details
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil-square" :href="route('property.edit', $unit)">
                                        Edit Property
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item 
                                        variant="danger" 
                                        icon="trash" 
                                        wire:click="confirmDelete({{ $unit->id }})"
                                    >
                                        Delete Property
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
                                        No units found matching your filters.
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
            {{ $this->units->links() }}
        </div>
    </div>

    <!-- Delete Modal -->
    <flux:modal title="delete-unit" class="md:w-96">
        <div class="space-y-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-full bg-red-100 p-2 dark:bg-red-900/30">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <flux:heading size="lg">
                        Delete Unit
                    </flux:heading>
                    <flux:text class="mt-2">
                        Are you sure you want to delete
                        <strong class="text-neutral-900 dark:text-white">{{ $selectedUnit?->title }}</strong>?
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
                    x-on:click="$flux.modal('delete-unit').close()"
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
                        Delete unit
                    </span>
                    <span wire:loading>
                        Deleting...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>