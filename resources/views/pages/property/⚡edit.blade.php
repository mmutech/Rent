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

    public ?Unit $unit = null;
    
    // Unit fields
    public string $title = '';
    public ?string $description = null;
    public ?float $amount = null;
    public ?int $bedrooms = null;
    public ?int $bathrooms = null;
    public ?int $kitchens = null;
    public ?int $living_rooms = null;
    public ?int $parking_spaces = null;
    public string $status = 'available';

    public ?int $compound_id = null;
    public ?int $property_id = null;

    // Image fields
    public $images = [];
    public $tempPrimaryImageIndex = 0;
    public $existingImages = [];
    public $imagesToDelete = [];

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
            'images.*' => ['nullable', 'image', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
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

    public function mount($property)
    {
        $user = Auth::user();
        
        if (!$property) {
            abort(404, 'Unit not found.');
        }
        
        // Load the unit with its images
        $this->unit = Unit::with('images')->findOrFail($property);
        
        // Check authorization
        if ($user->id !== $this->unit->created_by && !$user->hasRole('Admin')) {
            abort(403, 'You are not authorized to edit this unit.');
        }

        // Fill the form with existing data
        $this->title = $this->unit->title;
        $this->description = $this->unit->description;
        $this->amount = $this->unit->amount;
        $this->bedrooms = $this->unit->bedrooms;
        $this->bathrooms = $this->unit->bathrooms;
        $this->kitchens = $this->unit->kitchens;
        $this->living_rooms = $this->unit->living_rooms;
        $this->parking_spaces = $this->unit->parking_spaces;
        $this->status = $this->unit->status;
        $this->compound_id = $this->unit->compound_id;
        $this->property_id = $this->unit->property_id;

        // Load existing images with proper URL handling
        $this->existingImages = $this->unit->images->map(function($image) {
            return [
                'id' => $image->id,
                'image_path' => $image->image_path,
                'is_primary' => $image->is_primary,
                'image_url' => $this->getImageUrl($image->image_path),
            ];
        })->toArray();

        // Load dropdown data based on role
        if ($user->hasRole('Admin')) {
            $this->compounds = Compound::where('is_active', true)->get();
            $this->properties = Property::all();
        } else {
            $this->compounds = Compound::where('is_active', true)
                ->where('created_by', $user->id)
                ->get();
            $this->properties = Property::all();
        }
    }

    /**
     * Helper function to get proper image URL
     */
    private function getImageUrl($path)
    {
        // Check if it's an external URL
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // Check if it's a storage path
        if (Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }
        
        // If the path already contains 'storage', return as is
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }
        
        // Default fallback - try to serve from storage
        return Storage::url($path);
    }

    public function updateunit(): void
    {
        $validated = $this->validate();
        $validated['updated_by'] = Auth::id();

        DB::transaction(function () use ($validated) {
            // Update the unit
            $this->unit->update($validated);

            // Handle existing images to delete
            if (!empty($this->imagesToDelete)) {
                foreach ($this->imagesToDelete as $imageId) {
                    $image = PropertyImage::find($imageId);
                    if ($image) {
                        // Only delete local files, not external URLs
                        if (!filter_var($image->image_path, FILTER_VALIDATE_URL)) {
                            Storage::disk('public')->delete($image->image_path);
                        }
                        $image->delete();
                    }
                }
            }

            // Handle new image uploads
            if (!empty($this->images)) {
                $hasPrimary = PropertyImage::where('property_id', $this->unit->id)
                    ->where('is_primary', true)
                    ->exists();

                foreach ($this->images as $index => $image) {
                    $path = $image->store('properties/' . $this->unit->id, 'public');
                    
                    $isPrimary = (!$hasPrimary && $index === 0) || 
                                ($index === $this->tempPrimaryImageIndex && $hasPrimary);
                    
                    PropertyImage::create([
                        'property_id' => $this->unit->id,
                        'image_path' => $path,
                        'is_primary' => $isPrimary,
                    ]);
                }
            }
        });

        session()->flash('success', 'Property updated successfully.');
        $this->redirectRoute('property.index', navigate: true);
    }

    public function removeImage($index)
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
        
        if ($this->tempPrimaryImageIndex >= count($this->images)) {
            $this->tempPrimaryImageIndex = 0;
        }
    }

    public function setPrimaryImage($index)
    {
        $this->tempPrimaryImageIndex = $index;
    }

    public function removeExistingImage($imageId)
    {
        $image = PropertyImage::find($imageId);
        if ($image) {
            if ($image->is_primary) {
                $newPrimary = PropertyImage::where('property_id', $this->unit->id)
                    ->where('id', '!=', $imageId)
                    ->first();
                
                if ($newPrimary) {
                    $newPrimary->update(['is_primary' => true]);
                }
            }
            
            $this->imagesToDelete[] = $imageId;
            
            $this->existingImages = array_filter($this->existingImages, function($img) use ($imageId) {
                return $img['id'] !== $imageId;
            });
            
            $this->existingImages = array_values($this->existingImages);
        }
    }

    public function setExistingImageAsPrimary($imageId)
    {
        PropertyImage::where('property_id', $this->unit->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
        
        PropertyImage::where('id', $imageId)
            ->update(['is_primary' => true]);
        
        foreach ($this->existingImages as &$image) {
            $image['is_primary'] = ($image['id'] == $imageId);
        }
    }
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex items-center justify-between rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <div>
            <flux:heading size="xl">Edit Property</flux:heading>
            <flux:text class="mt-1">
                Edit property: {{ $title }}
            </flux:text>
        </div>

        <flux:button :href="route('property.index')" variant="ghost" icon="arrow-left" size="sm">
            Back to Properties
        </flux:button>
    </div>

    <!-- Form -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <flux:heading size="lg">Property Details</flux:heading>

        <form wire:submit.prevent="updateunit" class="flex flex-col gap-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Compound</label>
                    <select wire:model.live="compound_id" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-zinc-800">
                        <option value="">None</option>
                        @foreach($compounds as $compound)
                            <option value="{{ $compound->id }}">{{ $compound->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Property Type</label>
                    <select wire:model.live="property_id" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-zinc-800">
                        <option value="">None</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}">{{ $property->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:input wire:model.live="title" label="Title" placeholder="Enter property title" />
                <flux:input wire:model.live="amount" label="Amount" placeholder="Enter property amount" type="number" step="0.01" />
                
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Status</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-zinc-800">
                        <option value="available">Available</option>
                        <option value="sold">Sold</option>
                        <option value="rented">Rented</option>
                        <option value="under_construction">Under Construction</option>
                    </select>
                </div>
                
                <flux:input wire:model.live="bedrooms" label="Bedrooms" placeholder="Enter number of bedrooms" type="number" />
                <flux:input wire:model.live="bathrooms" label="Bathrooms" placeholder="Enter number of bathrooms" type="number" />
                <flux:input wire:model.live="kitchens" label="Kitchens" placeholder="Enter number of kitchens" type="number" />
                <flux:input wire:model.live="living_rooms" label="Living Rooms" placeholder="Enter number of living rooms" type="number" />
                <flux:input wire:model.live="parking_spaces" label="Parking Spaces" placeholder="Enter number of parking spaces" type="number" />
            </div>

            <div class="grid grid-cols-1 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:textarea wire:model.live="description" label="Description" placeholder="Enter property description" />
            </div>

            <!-- Existing Images Section -->
            @if(!empty($existingImages))
                <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                    <div class="mb-4">
                        <flux:heading size="md">Existing Images</flux:heading>
                        <flux:text class="mt-1 text-sm">
                            Click "Set Primary" to change the main image. Click "✕" to remove an image.
                        </flux:text>
                    </div>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        @foreach($existingImages as $image)
                            <div class="relative rounded-lg border border-neutral-200 p-2 dark:border-neutral-700">
                                <img src="{{ $image['image_url'] ?? Storage::url($image['image_path']) }}" 
                                    alt="Property image" 
                                    class="h-32 w-full rounded-lg object-cover" 
                                    onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}';" />
                                
                                <div class="mt-2 flex items-center justify-between">
                                    <button type="button" 
                                            wire:click="setExistingImageAsPrimary({{ $image['id'] }})"
                                            class="text-xs {{ $image['is_primary'] ? 'text-blue-600 font-bold' : 'text-neutral-500' }}">
                                        {{ $image['is_primary'] ? '★ Primary' : 'Set Primary' }}
                                    </button>
                                    <button type="button" 
                                            wire:click="removeExistingImage({{ $image['id'] }})"
                                            wire:confirm="Are you sure you want to remove this image?"
                                            class="text-sm text-red-600 hover:text-red-800">
                                        ✕
                                    </button>
                                </div>
                                @if($image['is_primary'])
                                    <div class="absolute left-2 top-2 rounded bg-blue-600 px-2 py-1 text-xs text-white">
                                        Primary
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- New Images Section -->
            <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="mb-4">
                    <flux:heading size="md">Add New Images</flux:heading>
                    <flux:text class="mt-1 text-sm">
                        Upload up to 10 images. Max 1MB each.
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
                                     alt="New image {{ $index + 1 }}" 
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
                <flux:button type="submit" variant="primary">Update Property</flux:button>
                <flux:button :href="route('property.index')" variant="ghost">Cancel</flux:button>
            </div>
        </form>
    </div>
</div>