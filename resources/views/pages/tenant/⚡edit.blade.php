<?php

use App\Models\User;
use App\Models\NextOfKin;
use App\Models\Property;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component
{
    use AuthorizesRequests;

    // Tenant fields
    public $tenantId;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $nin = '';

    // Next of Kin fields
    public string $nok_name = '';
    public string $nok_email = '';
    public string $relationship = '';
    public string $phone_number = '';
    public string $nok_address = '';
    public string $nok_nin = '';
    public $nokId;

    // Booking fields
    public $start_date;
    public $end_date;
    public $property_id;
    public $agent_id;
    public $bookingId;

    // Additional properties
    public $properties = [];
    public $agents = [];
    public $isProcessing = false;
    public $tenant;
    public $booking;
    public $nextOfKin;

    protected function rules(): array
    {
        $tenantId = $this->tenantId;
        
        return [
            // Tenant fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$tenantId}"],
            'phone' => ['required', 'string', 'max:20', "unique:users,phone,{$tenantId}"],
            'address' => ['required', 'string', 'max:255'],
            'nin' => ['required', 'string', 'size:11', "unique:users,nin,{$tenantId}"],
            
            // Next of Kin fields
            'nok_name' => ['required', 'string', 'max:255'],
            'nok_email' => ['required', 'email', 'max:255'],
            'nok_nin' => ['required', 'string', 'size:11'],
            'nok_address' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'relationship' => ['required', 'string', 'max:100'],

            // Booking fields
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'property_id' => ['required', 'exists:properties,id'],
            'agent_id' => ['nullable', 'exists:users,id'],
        ];
    }

    protected function messages(): array
    {
        return [
            // Tenant messages
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'Phone number is required.',
            'phone.unique' => 'This phone number is already registered.',
            'address.required' => 'Please provide an address.',
            'nin.required' => 'NIN is required.',
            'nin.size' => 'NIN must be exactly 11 characters.',
            'nin.unique' => 'This NIN is already registered.',

            // Next of Kin messages
            'nok_name.required' => 'Next of kin name is required.',
            'nok_email.required' => 'Next of kin email is required.',
            'nok_email.email' => 'Please enter a valid email address for next of kin.',
            'nok_address.required' => 'Next of kin address is required.',
            'phone_number.required' => 'Next of kin phone number is required.',
            'nok_nin.required' => 'Next of kin NIN is required.',
            'nok_nin.size' => 'Next of kin NIN must be exactly 11 characters.',
            'relationship.required' => 'Relationship with next of kin is required.',

            // Booking messages
            'start_date.required' => 'Start date is required.',
            'end_date.required' => 'End date is required.',
            'end_date.after' => 'End date must be after the start date.',
            'property_id.required' => 'Please select a property/unit.',
            'property_id.exists' => 'The selected property does not exist.',
        ];
    }

    public function mount($tenant)
    {
        $user = Auth::user();

        // Check permission
        if (!auth()->user()->hasPermissionTo('update-tenant')) {
            abort(403);
        }
        
        // Load the Tenant with relationships
        $this->tenant = User::with(['bookings', 'nextOfKin'])->findOrFail($tenant);
        $this->tenantId = $this->tenant->id; // Set tenant ID
        
        // Check authorization
        if ($user->id !== $this->tenant->created_by && !$user->hasRole('Admin')) {
            abort(403, 'You are not authorized to edit this tenant.');
        }

        // Load properties for dropdown
        $query = Property::with('compound')
            ->where('status', 'Available');

        if (!$user->hasRole('Admin')) {
            $query->where('created_by', $user->id);
        }

        $this->properties = $query->get();

        // Load agents for dropdown
        $this->agents = User::role('Agent')
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        // Fill tenant fields
        $this->name = $this->tenant->name;
        $this->email = $this->tenant->email;
        $this->phone = $this->tenant->phone;
        $this->address = $this->tenant->address;
        $this->nin = $this->tenant->nin;

        // Load next of kin
        $this->nextOfKin = NextOfKin::where('user_id', $this->tenantId)->first();

        if ($this->nextOfKin) {
            $this->nokId = $this->nextOfKin->id;
            $this->nok_name = $this->nextOfKin->name;
            $this->nok_email = $this->nextOfKin->email;
            $this->nok_nin = $this->nextOfKin->nin;
            $this->nok_address = $this->nextOfKin->address;
            $this->relationship = $this->nextOfKin->relationship;
            $this->phone_number = $this->nextOfKin->phone_number;
        }

        // Load the most recent booking
        $this->booking = Booking::with(['agent'])->where('user_id', $tenant)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($this->booking) {
            $this->bookingId = $this->booking->id;
            $this->property_id = $this->booking->property->title;
            $this->agent_id = $this->booking->agent->name ?? 'N/A';
            $this->start_date = $this->booking->start_date;
            $this->end_date = $this->booking->end_date;
        }

    }

    public function updateTenant(): void
    {
        $this->isProcessing = true;

        try {
            $validated = $this->validate();

            DB::transaction(function () use ($validated) {
                // Update tenant
                $this->tenant->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'nin' => $validated['nin'],
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                    'updated_by' => Auth::id(),
                ]);

                // Update or create Next of Kin
                if ($this->nokId) {
                    $this->nextOfKin->update([
                        'name' => $validated['nok_name'],
                        'email' => $validated['nok_email'],
                        'nin' => $validated['nok_nin'],
                        'address' => $validated['nok_address'],
                        'relationship' => $validated['relationship'],
                        'phone_number' => $validated['phone_number'],
                    ]);
                } else {
                    NextOfKin::create([
                        'user_id' => $this->tenant->id,
                        'name' => $validated['nok_name'],
                        'email' => $validated['nok_email'],
                        'nin' => $validated['nok_nin'],
                        'address' => $validated['nok_address'],
                        'relationship' => $validated['relationship'],
                        'phone_number' => $validated['phone_number'],
                    ]);
                }

                // Get the selected property
                $property = Property::with('category')->findOrFail($validated['property_id']);

                // Calculate total price
                $startDate = \Carbon\Carbon::parse($validated['start_date']);
                $endDate = \Carbon\Carbon::parse($validated['end_date']);
                $months = $startDate->diffInMonths($endDate);
                $totalPrice = $property->amount * ($months > 0 ? $months : 1);

                // Update or create Booking
                if ($this->bookingId) {
                    $this->booking->update([
                        'property_id' => $validated['property_id'],
                        'agent_id' => $validated['agent_id'] ?? Auth::id(),
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'],
                        'total_price' => $totalPrice,
                    ]);
                } else {
                    Booking::create([
                        'user_id' => $this->tenant->id,
                        'property_id' => $validated['property_id'],
                        'agent_id' => $validated['agent_id'] ?? '',
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'],
                        'total_price' => $totalPrice,
                        'status' => 'Pending',
                    ]);
                }
            });

            session()->flash('success', 'Tenant updated successfully.');
            
            // Fixed: Pass tenant ID as named parameter
            $this->redirectRoute('tenant.show', $this->tenant->id, navigate: true);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
            $this->isProcessing = false;
            throw $e;
        }

        $this->isProcessing = false;
    }

    // Helper method to clear form
    public function resetForm(): void
    {
        $this->reset([
            'name', 'email', 'phone', 'address', 'nin',
            'nok_name', 'nok_email', 'relationship', 
            'phone_number', 'nok_address', 'nok_nin',
            'start_date', 'end_date', 'property_id', 'agent_id'
        ]);
        $this->resetValidation();
    }

    // Helper to get property details when a unit is selected
    public function updatedPropertyId($value)
    {
        if ($value) {
            $property = Property::with('category', 'compound')->find($value);
            $this->dispatch('property-selected', property: $property);
        }
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Edit Tenant</flux:heading>
            <flux:text class="mt-1">
                Edit Tenant Details: {{ $name }}
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('tenant.index')" variant="ghost" icon="arrow-left">
                Back to Tenant
            </flux:button>
        </div>
    </div>

    <!-- Form -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="lg">Tenant Details</flux:heading>

        <form wire:submit.prevent="updateTenant" class="flex flex-col gap-4">
            <!-- Tenant Details -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="name" label="Full Name" placeholder="Enter Full Name" required />
                <flux:input wire:model.live="email" label="Email" placeholder="Enter valid email" required />
                <flux:input wire:model.live="phone" label="Phone Number" placeholder="Enter valid Phone Number" required />
                <flux:input wire:model.live="nin" label="NIN" placeholder="Enter valid NIN" type="number" required />
            </div>

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:textarea wire:model.live="address" label="Address" placeholder="Enter address" required />
            </div>

            <!-- Next of Kin Details -->
            <flux:heading size="lg">Next of Kin</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="nok_name" label="NOK Full Name" placeholder="Enter NOK Full Name" required />
                <flux:input wire:model.live="nok_email" label="NOK Email" placeholder="Enter valid NOK email" required />
                <flux:input wire:model.live="phone_number" label="NOK Phone Number" placeholder="Enter valid NOK Phone Number" required />
                <flux:input wire:model.live="nok_nin" label="NOK NIN" placeholder="Enter valid NOK NIN" type="number" required />
                <flux:select wire:model.live="relationship" label="Relationship" required>
                    <option value="">Choose a relationship...</option>
                    <option value="Spouse">Spouse</option>
                    <option value="Child">Child</option>
                    <option value="Parent">Parent</option>
                    <option value="Sibling">Sibling</option>
                    <option value="Friend">Friend</option>
                    <option value="Guardian">Guardian</option>
                    <option value="Other">Other</option>
                </flux:select>
            </div>

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:textarea wire:model.live="nok_address" label="NOK Address" placeholder="Enter NOK address" required />
            </div>

            <!-- Booking Details -->
            <flux:heading size="lg">Booking</flux:heading>
            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:select wire:model.live="property_id" label="Property" required>
                    <option value="">Choose a property...</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}">
                            {{ $property->compound->name ?? 'Unknown' }} - Unit ({{ $property->title }})
                            (₦{{ number_format($property->amount) }}/month)
                        </option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="start_date" label="Start Date" type="date" required />
                <flux:input wire:model.live="end_date" label="End Date" type="date" required />
            </div>

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:select wire:model.live="agent_id" label="Assigned Agent">
                    <option value="">Select agent...</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Update Tenant</span>
                    <span wire:loading>Updating...</span>
                </flux:button>
                <flux:button :href="route('tenant.index')" variant="ghost">Cancel</flux:button>
            </div>
        </form>
    </div>
</div>