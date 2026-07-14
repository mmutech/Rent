<?php

use App\Models\User;
use App\Models\NextOfKin;
use App\Models\Property;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $nin = '';

    public string $nok_name = '';
    public string $nok_email = '';
    public string $relationship = '';
    public string $phone_number = '';
    public string $nok_address = '';
    public string $nok_nin = '';

    // Booking fields
    public $start_date;
    public $end_date;
    public $property_id;
    public $agent_id;

    // Additional properties
    public $properties = [];
    public $agents = [];
    public $isProcessing = false;

    protected function rules(): array
    {
        return [
            // Tenant fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'address' => ['required', 'string', 'max:255'],
            'nin' => ['required', 'string', 'size:11', 'unique:users,nin'], // NIN is typically 11 digits
            
            // Next of Kin fields
            'nok_name' => ['required', 'string', 'max:255'],
            'nok_email' => ['required', 'email', 'max:255'],
            'nok_nin' => ['required', 'string', 'size:11'],
            'nok_address' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'relationship' => ['required', 'string', 'max:100'],

            // Bookings fields
            'start_date' => ['required', 'date', 'after_or_equal:today'],
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
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'end_date.required' => 'End date is required.',
            'end_date.after' => 'End date must be after the start date.',
            'property_id.required' => 'Please select a property/unit.',
            'property_id.exists' => 'The selected property does not exist.',
        ];
    }

    public function createTenant(): void
    {
        $this->isProcessing = true;

        try {
            $validated = $this->validate();

            DB::transaction(function () use ($validated) {
                // Generate a random password
                $password = Str::random(12);

                $tenant = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'nin' => $validated['nin'],
                    'address' => $validated['address'],
                    'phone' => $validated['phone'],
                    'password' => Hash::make($password),
                    'is_active' => true,
                    'created_by' => Auth::id(),
                ]);

                // Assign the Tenant role
                $tenant->assignRole('Tenant');

                // Create the Next of Kin
                NextOfKin::create([
                    'user_id' => $tenant->id,
                    'name' => $validated['nok_name'],
                    'email' => $validated['nok_email'],
                    'nin' => $validated['nok_nin'],
                    'address' => $validated['nok_address'],
                    'relationship' => $validated['relationship'],
                    'phone_number' => $validated['phone_number'],
                ]);

                // Get the selected property with its category details
                $property = Property::with('category')->findOrFail($validated['property_id']);

                // Calculate total price (example: rent * months)
                $startDate = \Carbon\Carbon::parse($validated['start_date']);
                $endDate = \Carbon\Carbon::parse($validated['end_date']);
                $months = $startDate->diffInMonths($endDate);
                $totalPrice = $property->amount * ($months > 0 ? $months : 1);

                // Create the Booking
                Booking::create([
                    'user_id' => $tenant->id,
                    'property_id' => $validated['property_id'],
                    'agent_id' => $validated['agent_id'] ?? Auth::id(),
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'total_price' => $totalPrice,
                    'status' => 'Pending',
                ]);

            });

            session()->flash('success', 'Tenant created successfully.');

            $this->redirectRoute('tenant.index', navigate: true);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
            $this->isProcessing = false;
            throw $e; // Or handle gracefully
        }

        $this->isProcessing = false;
    }

    public function mount()
    {
        // Check permission
        if (!auth()->user()->hasPermissionTo('create-tenant')) {
            abort(403);
        }

        // Get the current authenticated user
        $user = Auth::user();

        // Load available properties based on user role
        $query = Property::with('category')
            ->where('status', 'Available');

        // Load available agents
        $query2 = User::role('Agent')
            ->where('is_active', true)
            ->where('is_verified', true);

        // If user is not Admin, only show properties they created
        if (!$user->hasRole('Admin')) {
            $query->where('created_by', $user->id);
            $query2->where('created_by', $user->id);
        }

        $this->properties = $query->get();
        $this->agents = $query2->get();
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
            $property = Property::with('category')->find($value);
            // You can emit an event to update the UI or set additional fields
            $this->dispatch('property-selected', property: $property);
        }
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">New Tenant</flux:heading>
            <flux:text class="mt-1">
                Create a new Tenant.
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('tenant.index')" variant="ghost" icon="arrow-left">
                Back to Tenants
            </flux:button>
        </div>
    </div>

    <!-- Form -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="lg">Tenant Details</flux:heading>

        <form wire:submit.prevent="createTenant" class="flex flex-col gap-4">
            <!-- Tenant Details -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="name" label="Full Name" placeholder="Enter Full Name" />
                <flux:input wire:model.live="email" label="Email" placeholder="Enter valid email" />
                <flux:input wire:model.live="phone" label="Phone Number" placeholder="Enter valid Phone Number" />
                
                <flux:input wire:model.live="nin" label="NIN" placeholder="Enter valid nin" type="number" />
            </div>

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:textarea wire:model.live="address" label="Address" placeholder="Enter address" />
            </div>

            <!-- // Next of Kin Details -->
            <flux:heading size="lg">Tenant Next of Kin</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                
                <flux:input wire:model.live="nok_name" label="NOK Full Name" placeholder="Enter NOK Full Name" />
                <flux:input wire:model.live="nok_email" label="NOK Email" placeholder="Enter valid nok email" />
                <flux:input wire:model.live="phone_number" label="NOK Phone Number" placeholder="Enter valid nok Phone Number" />
                <flux:input wire:model.live="nok_nin" label="NOK NIN" placeholder="Enter valid nok nin" type="number" />
                <flux:select wire:model.live="relationship" label="Relationship">
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
                <flux:textarea wire:model.live="nok_address" label="NOK Address" placeholder="Enter nok address" />
            </div>

            <!-- // Booking Details -->
            <flux:heading size="lg">Booking</flux:heading>
            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:select wire:model.live="property_id" label="Property">
                    <option value="">Choose a property...</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}">
                            {{ $property->compound->name ?? 'Unknown' }}  - Unit ({{ $property->title }})
                            (₦{{ number_format($property->amount) }}/month)
                        </option>
                    @endforeach
                </flux:select>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="start_date" label="Start Date" type="date" />
                <flux:input wire:model.live="end_date" label="End Date" type="date" />
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
                <flux:button type="submit" variant="primary">Create Tenant</flux:button>
                <flux:button :href="route('property.index')" variant="ghost">Cancel</flux:button>
            </div>
        </form>
    </div>
</div>