<?php

use Livewire\Component;
use App\Models\Compound;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public Compound $compound;
    
    public string $name = '';
    public string $address = '';
    public ?string $landmark = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $zip_code = null;
    
    public int $total_units = 0;
    
    public ?string $description = null;
    
    public ?string $google_map_url = null;
    public ?float $latitude = null;
    public ?float $longitude = null;
    
    public bool $fence_walled = false;
    public bool $street_lights = false;
    public bool $playground = false;
    public bool $security_guard = false;
    public bool $cctv = false;
    public bool $gated = false;

    public function mount(Compound $compound): void
    {
        $this->compound = $compound;
        
        // Fill the form with existing data
        $this->name = $compound->name;
        $this->address = $compound->address;
        $this->landmark = $compound->landmark;
        $this->city = $compound->city;
        $this->state = $compound->state;
        $this->zip_code = $compound->zip_code;
        $this->total_units = $compound->total_units;
        $this->description = $compound->description;
        $this->google_map_url = $compound->google_map_url;
        $this->latitude = $compound->latitude;
        $this->longitude = $compound->longitude;
        $this->fence_walled = (bool) $compound->fence_walled;
        $this->street_lights = (bool) $compound->street_lights;
        $this->playground = (bool) $compound->playground;
        $this->security_guard = (bool) $compound->security_guard;
        $this->cctv = (bool) $compound->cctv;
        $this->gated = (bool) $compound->gated;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'address' => ['required', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            
            'google_map_url' => ['nullable', 'url'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'total_units' => ['required', 'integer', 'min:0'],
            
            'fence_walled' => ['boolean'],
            'street_lights' => ['boolean'],
            'playground' => ['boolean'],
            'security_guard' => ['boolean'],
            'cctv' => ['boolean'],
            'gated' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Compound name is required.',
            'address.required' => 'Compound address is required.',
            'description.required' => 'Please provide a description.',
            'google_map_url.url' => 'Please enter a valid Google Maps URL.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }

    public function updateCompound(): void
    {
        $validated = $this->validate();
        
        $validated['updated_by'] = Auth::id();

        DB::transaction(function () use ($validated) {
            $this->compound->update($validated);
        });

        session()->flash('success', 'Compound updated successfully.');

        $this->redirectRoute('compound.show', ['compound' => $this->compound], navigate: true);
    }

};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex items-center justify-between rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <div>
            <flux:heading size="xl">Edit Compound</flux:heading>
            <flux:text class="mt-1">
                Update compound details.
            </flux:text>
        </div>

        <flux:button :href="route('compound.index')" variant="ghost" icon="arrow-left">
            Back to Compounds
        </flux:button>
    </div>

    <!-- Form -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="lg">Compound Details</flux:heading>

        <form wire:submit="updateCompound" class="flex flex-col gap-4">
            @csrf
            
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model="name" label="Name" placeholder="Enter compound name" />
                <flux:input wire:model="address" label="Address" placeholder="Enter compound address" />
                <flux:input wire:model="landmark" label="Landmark" placeholder="Enter compound landmark" />
                <flux:input wire:model="city" label="City" placeholder="Enter compound city" />
                <flux:input wire:model="state" label="State" placeholder="Enter compound state" />
                <flux:input wire:model="zip_code" label="Zip Code" placeholder="Enter compound zip code" />
                <flux:input wire:model="total_units" label="Total Units" placeholder="Enter total units" type="number" />
            </div>

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:textarea wire:model="description" label="Description" placeholder="Enter compound description" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model="google_map_url" label="Google Map URL" type="url" placeholder="Enter Google Map URL" />
                <flux:input wire:model="latitude" label="Latitude" type="number" step="any" placeholder="Enter latitude" />
                <flux:input wire:model="longitude" label="Longitude" type="number" step="any" placeholder="Enter longitude" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:checkbox wire:model="fence_walled" label="Fence/Walled" />
                <flux:checkbox wire:model="street_lights" label="Street Lights" />
                <flux:checkbox wire:model="playground" label="Playground" />
                <flux:checkbox wire:model="security_guard" label="Security Guard" />
                <flux:checkbox wire:model="cctv" label="CCTV" />
                <flux:checkbox wire:model="gated" label="Gated Community" />
            </div>

            @if($errors->any())
                <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                    <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary">Update Compound</flux:button>
                <flux:button :href="route('compound.index')" variant="ghost">Cancel</flux:button>
            </div>
        </form>
    </div>
</div>