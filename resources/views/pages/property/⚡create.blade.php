<?php

use App\Models\Unit;
use App\Models\Compound;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $title = '';
    public ?string $description = null;
    public ?float $amount = null;
    public ?int $bedrooms = null;
    public ?int $bathrooms = null;
    public ?int $kitchens = null;
    public ?int $living_rooms = null;
    public ?int $parking_spaces = null;
    public string $status = 'available'; // Default status

    public ?int $compound_id = null;
    public ?int $property_id = null;

    // Image fields
    public $images = []; // For multiple image uploads
    public $tempPrimaryImageIndex = 0; // Track which image is primary

    // For dropdowns
    public $compounds = [];
    public $properties = [];

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'bedrooms' => ['required', 'integer', 'min:0'],
            'bathrooms' => ['required', 'integer', 'min:0'],
            'kitchens' => ['required', 'integer', 'min:0'],
            'living_rooms' => ['required', 'integer', 'min:0'],
            'parking_spaces' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:available,sold,rented,under_construction'],
            'compound_id' => ['nullable', 'exists:compounds,id'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'images.*' => ['nullable', 'image', 'max:2048'], // 1MB max per image
            'images' => ['nullable', 'array', 'max:10'], // Max 10 images
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Unit title is required.',
            'description.required' => 'Please provide a description.',
            'amount.required' => 'Unit amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'bedrooms.required' => 'Number of bedrooms is required.',
            'bathrooms.required' => 'Number of bathrooms is required.',
            'kitchens.required' => 'Number of kitchens is required.',
            'living_rooms.required' => 'Number of living rooms is required.',
            'parking_spaces.required' => 'Number of parking spaces is required.',
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
            'compound_id.exists' => 'Selected compound does not exist.',
            'property_id.exists' => 'Selected property does not exist.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.max' => 'Each image must not exceed 1MB.',
            'images.max' => 'You can upload a maximum of 10 images.',
        ];
    }

    public function createunit(): void
    {
        $validated = $this->validate();
        
        $validated['created_by'] = Auth::id();

        DB::transaction(function () use ($validated) {
           $unit = Unit::create($validated);

            // Handle image uploads
            if (!empty($this->images)) {
                foreach ($this->images as $index => $image) {
                    $path = $image->store('properties/' . $unit->id, 'public');
                    
                    PropertyImage::create([
                        'property_id' => $unit->id, // Assuming this links to unit
                        'image_path' => $path,
                        'is_primary' => ($index === $this->tempPrimaryImageIndex),
                    ]);
                }
            }
        });

        session()->flash('success', 'Property created successfully.');

        $this->redirectRoute('property.index', navigate: true);
    }

    // Remove a temporary image before upload
    public function removeImage($index)
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
        
        // Reset primary index if needed
        if ($this->tempPrimaryImageIndex >= count($this->images)) {
            $this->tempPrimaryImageIndex = 0;
        }
    }

    // Set primary image
    public function setPrimaryImage($index)
    {
        $this->tempPrimaryImageIndex = $index;
    }

    public function mount()
    {
        $user = Auth::user();
        
        // Check if user has Admin role using Spatie
        if ($user->hasRole('Admin')) {
            // Admin sees all active compounds
            $this->compounds = Compound::where('is_active', true)->get();
            $this->properties = Property::all();
        } else {
            // Non-admin users see only their own records
            $this->compounds = Compound::where('is_active', true)
                ->where('created_by', $user->id)
                ->get();
            
            $this->properties = Property::all();
        }
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex items-center justify-between rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <div>
            <flux:heading size="xl">New Property</flux:heading>
            <flux:text class="mt-1">
                Create a new property.
            </flux:text>
        </div>

        <flux:button :href="route('property.index')" variant="ghost" icon="arrow-left">
            Back to Property
        </flux:button>
    </div>

    <!-- Form -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="lg">Property Details</flux:heading>

        <form wire:submit.prevent="createunit" class="flex flex-col gap-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Compound</label>
                    <select wire:model.live="compound_id" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-zinc-800">
                        <option value="">None</option>
                        @foreach($this->compounds as $compound)
                            <option value="{{ $compound->id }}">{{ $compound->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Property Type</label>
                    <select wire:model.live="property_id" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-zinc-800">
                        <option value="">None</option>
                        @foreach($this->properties as $property)
                            <option value="{{ $property->id }}">{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                
                <flux:input wire:model.live="title" label="Title" placeholder="Enter property title" />
                <flux:input wire:model.live="amount" label="Amount" placeholder="Enter property amount" type="number" step="0.01" />
                
                <flux:input wire:model.live="bedrooms" label="Bedrooms" placeholder="Enter number of bedrooms" type="number" />
                <flux:input wire:model.live="bathrooms" label="Bathrooms" placeholder="Enter number of bathrooms" type="number" />
                <flux:input wire:model.live="kitchens" label="Kitchens" placeholder="Enter number of kitchens" type="number" />
                <flux:input wire:model.live="living_rooms" label="Living Rooms" placeholder="Enter number of living rooms" type="number" />
                <flux:input wire:model.live="parking_spaces" label="Parking Spaces" placeholder="Enter number of parking spaces" type="number" />
            </div>

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:textarea wire:model.live="description" label="Description" placeholder="Enter property description" />
            </div>

            <!-- Images Section -->
            <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-4">
                    <flux:heading size="md">Unit Images</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        Upload up to 10 images. First image will be primary by default (max 5MB each).
                    </flux:text>
                </div>

                <!-- File Input -->
                <div class="mb-4">
                    <input type="file" wire:model.live="images" multiple accept="image/*" 
                           class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-zinc-800" />
                    @error('images.*') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    @error('images') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>

                <!-- Image Previews -->
                @if(!empty($images))
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        @foreach($images as $index => $image)
                            <div class="relative rounded-lg border border-neutral-200 p-2 dark:border-neutral-700">
                                <img src="{{ $image->temporaryUrl() }}" 
                                     alt="Unit image {{ $index + 1 }}" 
                                     class="h-32 w-full rounded-lg object-cover" />
                                
                                <div class="mt-2 flex items-center justify-between">
                                    <button type="button" 
                                            wire:click="setPrimaryImage({{ $index }})"
                                            class="text-xs {{ $index === $tempPrimaryImageIndex ? 'text-blue-600 font-bold' : 'text-neutral-500' }}">
                                        {{ $index === $tempPrimaryImageIndex ? '★ Primary' : 'Set Primary' }}
                                    </button>
                                    <button type="button" 
                                            wire:click="removeImage({{ $index }})"
                                            class="text-sm text-red-600 hover:text-red-800">
                                        ✕
                                    </button>
                                </div>
                                @if($index === $tempPrimaryImageIndex)
                                    <div class="absolute left-2 top-2 rounded bg-blue-600 px-2 py-1 text-xs text-white">
                                        Primary
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <flux:button type="submit" variant="primary">Create Property</flux:button>
                <flux:button :href="route('property.index')" variant="ghost">Cancel</flux:button>
            </div>
        </form>
    </div>
</div>