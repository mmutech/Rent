<?php

use App\Models\Compound;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component
{
    public string $name = '';
    public string $address = '';
    public ?string $landmark = null;
    public ?string $city = null;
    public ?string $state = null;
    public ?string $zip_code = null;

    // Consider removing these if they will be calculated automatically
    public int $total_properties = 0;

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
            
            'total_properties' => ['required', 'integer', 'min:0'],

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

    public function mount()
    {
        if (!auth()->user()->hasPermissionTo('create-compound')) {
            abort(403);
        }
    }

    public function createCompound(): void
    {
        $validated = $this->validate();
        
        $validated['created_by'] = Auth::id();

        DB::transaction(function () use ($validated) {
            Compound::create($validated);
        });

        session()->flash('success', 'Compound created successfully.');

        $this->redirectRoute('compound.index', navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">New Compound</flux:heading>
            <flux:text class="mt-1">
                Create a new compound.
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('compound.index')" variant="ghost" icon="arrow-left">
                Back to Compounds
            </flux:button>
        </div>
    </div>

    <!-- Form -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="lg">Compound Details</flux:heading>

        <form wire:submit.prevent="createCompound" class="flex flex-col gap-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="name" label="Name" placeholder="Enter compound name" />
                <flux:input wire:model.live="address" label="Address" placeholder="Enter compound address" />
                <flux:input wire:model.live="landmark" label="Landmark" placeholder="Enter compound landmark" />
                <flux:input wire:model.live="city" label="City" placeholder="Enter compound city" />
                <flux:input wire:model.live="state" label="State" placeholder="Enter compound state" />
                <flux:input wire:model.live="zip_code" label="Zip Code" placeholder="Enter compound zip code" />
                <flux:input wire:model.live="total_properties" label="Total Properties" placeholder="Enter total properties" type="number" />
            </div>

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:textarea wire:model.live="description" label="Description" placeholder="Enter compound description" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="google_map_url" label="Google Map URL" type="url" placeholder="Enter Google Map URL" />
                <flux:input wire:model.live="latitude" label="Latitude" type="number" placeholder="Enter latitude" />
                <flux:input wire:model.live="longitude" label="Longitude" type="number" placeholder="Enter longitude" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:checkbox wire:model.live="fence_walled" label="Fence/Walled" />
                <flux:checkbox wire:model.live="street_lights" label="Street Lights" />
                <flux:checkbox wire:model.live="playground" label="Playground" />
                <flux:checkbox wire:model.live="security_guard" label="Security Guard" />
                <flux:checkbox wire:model.live="cctv" label="CCTV" />
                <flux:checkbox wire:model.live="gated" label="Gated Community" />
            </div>

            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary">Create Compound</flux:button>
                <flux:button :href="route('compound.index')" variant="ghost">Cancel</flux:button>
            </div>
        </form>
    </div>
</div>