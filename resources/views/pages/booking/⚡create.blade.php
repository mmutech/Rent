<?php

use App\Models\User;
use App\Models\Property;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component
{
    use AuthorizesRequests;

    // Booking fields
    public $user_id;
    public $property_id;
    public $agent_id;
    public $start_date;
    public $end_date;
    public $total_price;
    public $status = 'Pending';
    public $notes;

    // Additional properties
    public $tenants = [];
    public $properties = [];
    public $agents = [];
    public $isProcessing = false;
    public $selectedProperty = null;
    public $monthlyRent = 0;
    public $totalMonths = 0;
    public $calculatedTotal = 0;
    public $showAgentField = false; // Always show agent field

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'agent_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'user_id.required' => 'Please select a tenant.',
            'user_id.exists' => 'The selected tenant does not exist.',
            'property_id.required' => 'Please select a property.',
            'property_id.exists' => 'The selected property does not exist.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'end_date.required' => 'End date is required.',
            'end_date.after' => 'End date must be after the start date.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }

    public function mount()
    {
        // Check permission
        if (!auth()->user()->hasPermissionTo('create-booking')) {
            abort(403);
        }

        $user = Auth::user();

        // Load tenants (users with Tenant role)
        $this->tenants = User::role('Tenant')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        // Load available properties
        $query = Property::with('compound')
            ->where('status', 'Available');

        if (!$user->hasRole('Admin')) {
            $query->where('created_by', $user->id);
        }

        $this->properties = $query->get();

        // Load agents (users with Agent role)
        $this->agents = User::role('Agent')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Set default agent to current user if they're an agent, otherwise null
        if ($user->hasRole('Agent')) {
            $this->agent_id = $user->id;
        } else {
            $this->agent_id = null; // Ensure it's null for non-agents
        }

        // Set default status
        $this->status = 'Pending';
        
        // Always show agent field
        $this->showAgentField = true;
    }

    public function createBooking(): void
    {
        $this->isProcessing = true;

        try {
            $validated = $this->validate();

            // Verify tenant has the correct role
            $tenant = User::find($validated['user_id']);
            if (!$tenant->hasRole('Tenant')) {
                session()->flash('error', 'The selected user is not a tenant.');
                $this->isProcessing = false;
                return;
            }

            // Check if property is still available
            $property = Property::find($validated['property_id']);
            if (!$property || $property->status !== 'Available') {
                session()->flash('error', 'This property is no longer available.');
                $this->isProcessing = false;
                return;
            }

            // Check for date conflicts
            $conflictingBooking = Booking::where('property_id', $validated['property_id'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                        });
                })
                ->whereIn('status', ['Pending', 'Confirmed'])
                ->exists();

            if ($conflictingBooking) {
                session()->flash('error', 'This property is already booked for the selected dates.');
                $this->isProcessing = false;
                return;
            }

            // Calculate total price
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            $months = $startDate->diffInMonths($endDate);
            $totalPrice = $property->amount * ($months > 0 ? $months : 1);

            $booking = null; // Initialize variable

            DB::transaction(function () use ($validated, $totalPrice, &$booking) {
                // Create the booking with auto-generated reference
                $booking = Booking::create([
                    'user_id' => $validated['user_id'] ?? Auth::id(),
                    'property_id' => $validated['property_id'],
                    'agent_id' => $validated['agent_id'] ?? Auth::id(),
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'total_price' => $totalPrice,
                    'status' => 'Pending',
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                // Update property status to Reserved
                // $property = Property::find($validated['property_id']);
                // $property->update(['status' => 'Reserved']);
            });

            session()->flash('success', "Booking '{$booking->booking_reference}' created successfully.");

            // Redirect to booking show page
            $this->redirectRoute('booking.show', ['booking' => $booking->id], navigate: true);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
            $this->isProcessing = false;
            
            // Log the error for debugging
            \Log::error('Booking creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }

        $this->isProcessing = false;
    }

    // Calculate total when dates or property changes
    public function updatedStartDate($value)
    {
        $this->calculateTotal();
    }

    public function updatedEndDate($value)
    {
        $this->calculateTotal();
    }

    public function updatedPropertyId($value)
    {
        if ($value) {
            $this->selectedProperty = Property::with('compound')->find($value);
            $this->monthlyRent = $this->selectedProperty->amount ?? 0;
            $this->calculateTotal();
            
            // Emit event for UI updates
            $this->dispatch('property-selected', property: $this->selectedProperty);
        } else {
            $this->selectedProperty = null;
            $this->monthlyRent = 0;
            $this->calculatedTotal = 0;
            $this->totalMonths = 0;
        }
    }

    private function calculateTotal(): void
    {
        if ($this->property_id && $this->start_date && $this->end_date) {
            $property = Property::find($this->property_id);
            if ($property) {
                $startDate = \Carbon\Carbon::parse($this->start_date);
                $endDate = \Carbon\Carbon::parse($this->end_date);
                $this->totalMonths = $startDate->diffInMonths($endDate);
                $this->calculatedTotal = $property->amount * ($this->totalMonths > 0 ? $this->totalMonths : 1);
                $this->total_price = $this->calculatedTotal;
            }
        } else {
            $this->totalMonths = 0;
            $this->calculatedTotal = 0;
            $this->total_price = null;
        }
    }

    // Reset form
    public function resetForm(): void
    {
        $this->reset([
            'user_id', 'property_id', 'agent_id', 
            'start_date', 'end_date', 'status', 'notes',
            'total_price', 'selectedProperty', 'monthlyRent', 
            'totalMonths', 'calculatedTotal'
        ]);
        $this->resetValidation();
        
        // Reset agent to current user if agent, else null
        $user = Auth::user();
        if ($user->hasRole('Agent')) {
            $this->agent_id = $user->id;
        } else {
            $this->agent_id = null;
        }
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">New Booking</flux:heading>
            <flux:text class="mt-1">
                Create a new booking for a tenant.
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('booking.index')" variant="ghost" icon="arrow-left">
                Back to Bookings
            </flux:button>
        </div>
    </div>

    <!-- Flash Messages -->
    <x-flash-message />

    <!-- Form -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="lg">Booking Details</flux:heading>

        <form wire:submit.prevent="createBooking" class="flex flex-col gap-4">
            <!-- Select Tenant & Property -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                @if(!auth()->user()->hasRole('Tenant'))    
                    <flux:select wire:model.live="user_id" label="Select Tenant" required>
                        <option value="">Choose a tenant...</option>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}">
                                {{ $tenant->name }} ({{ $tenant->email }})
                            </option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:select wire:model.live="property_id" label="Select Property" required>
                    <option value="">Choose a property...</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}">
                            {{ $property->compound->name ?? 'Unknown' }} - {{ $property->title }}
                            (₦{{ number_format($property->amount) }}/month)
                        </option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Property Details (shown when selected) -->
            @if($selectedProperty)
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-700 dark:bg-blue-900/20">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div>
                            <flux:text class="text-xs font-medium text-blue-600 dark:text-blue-400">Property</flux:text>
                            <flux:text class="text-sm font-medium">{{ $selectedProperty->title }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-blue-600 dark:text-blue-400">Compound</flux:text>
                            <flux:text class="text-sm">{{ $selectedProperty->compound->name ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-blue-600 dark:text-blue-400">Monthly Rent</flux:text>
                            <flux:text class="text-sm font-medium text-blue-700 dark:text-blue-300">
                                ₦{{ number_format($selectedProperty->amount, 2) }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-blue-600 dark:text-blue-400">Status</flux:text>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Available
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Date Selection -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="start_date" label="Start Date" type="date" required />
                <flux:input wire:model.live="end_date" label="End Date" type="date" required />
                <div>
                    <flux:text class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Duration</flux:text>
                    <flux:text class="mt-1 text-lg font-semibold text-neutral-900 dark:text-white">
                        @if($totalMonths > 0)
                            {{ $totalMonths }} month{{ $totalMonths > 1 ? 's' : '' }}
                        @elseif($start_date && $end_date)
                            <span class="text-sm text-neutral-500">Calculate dates</span>
                        @else
                            <span class="text-sm text-neutral-500">Select dates</span>
                        @endif
                    </flux:text>
                </div>
            </div>

            <!-- Price Summary -->
            @if($calculatedTotal > 0)
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/20">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">Total Price</flux:text>
                            <flux:text class="text-2xl font-bold text-green-700 dark:text-green-300">
                                ₦{{ number_format($calculatedTotal, 2) }}
                            </flux:text>
                        </div>
                        <div class="text-sm text-green-600 dark:text-green-400">
                            ₦{{ number_format($monthlyRent, 2) }} × {{ $totalMonths > 0 ? $totalMonths : 1 }} month{{ $totalMonths > 1 ? 's' : '' }}
                            @if($totalMonths == 0 && $start_date && $end_date)
                                <span class="text-xs">(Minimum 1 month)</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Agent Field - Always Visible -->
            @if(!auth()->user()->hasRole(['Agent', 'Tenant']))
                <div class="grid grid-cols-1 gap-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                    <flux:select wire:model.live="agent_id" label="Assigned Agent (Optional)">
                        <option value="">Select agent...</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:text class="text-sm text-yellow-600 dark:text-yellow-400">
                        ⚠️ You are not an agent. Please assign an agent to this booking or leave it unassigned.
                    </flux:text>
                </div>
            @endif

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:textarea wire:model.live="notes" label="Notes" placeholder="Enter any additional notes..." rows="3" />
            </div>

            <!-- Submit -->
            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Create Booking</span>
                    <span wire:loading>Creating...</span>
                </flux:button>
                <flux:button type="button" variant="ghost" wire:click="resetForm">
                    Reset
                </flux:button>
                <flux:button :href="route('booking.index')" variant="ghost">Cancel</flux:button>
            </div>
        </form>
    </div>

    <!-- Quick Actions -->
    <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <div class="flex flex-wrap gap-2">
            <flux:button variant="ghost" icon="user-plus" size="sm" :href="route('tenant.create')">
                Create New Tenant
            </flux:button>
            <flux:button variant="ghost" icon="home-modern" size="sm" :href="route('property.create')">
                Create New Property
            </flux:button>
        </div>
    </div>
</div>