<?php

use App\Models\Unit;
use App\Models\Compound;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

new class extends Component
{
    public ?Unit $unit = null;
    
    // For dropdowns (if needed for related data)
    public $compound = null;
    public $property = null;
    public $images = [];
    public $primaryImage = null;

    public function mount($property)
    {
        $user = Auth::user();
        
        if (!$property) {
            abort(404, 'Property not found.');
        }
        
        // Load the unit with its images and relationships
        $this->unit = Unit::with(['images', 'compound', 'property', 'primaryImage', 'createdBy'])
            ->findOrFail($property);
        
        // Check if user can view this unit
        if ($user->id !== $this->unit->created_by && !$user->hasRole('Admin')) {
            abort(403, 'You are not authorized to view this unit.');
        }

        // Load related data
        if ($this->unit->compound_id) {
            $this->compound = Compound::find($this->unit->compound_id);
        }
        
        if ($this->unit->property_id) {
            $this->property = Property::find($this->unit->property_id);
        }

        // Load images with proper URLs
        $this->images = $this->unit->images->map(function($image) {
            return [
                'id' => $image->id,
                'image_path' => $image->image_path,
                'is_primary' => $image->is_primary,
                'image_url' => $this->getImageUrl($image->image_path),
            ];
        })->toArray();

        // Get primary image
        $primaryImage = $this->unit->images->where('is_primary', true)->first();
        if ($primaryImage) {
            $this->primaryImage = [
                'id' => $primaryImage->id,
                'image_path' => $primaryImage->image_path,
                'image_url' => $this->getImageUrl($primaryImage->image_path),
            ];
        } elseif (!empty($this->images)) {
            // If no primary image, use the first one
            $this->primaryImage = $this->images[0];
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

    /**
     * Get status badge color
     */
    private function getStatusColor($status)
    {
        return match($status) {
            'Available' => 'bg-green-500',
            'Occupied' => 'bg-red-500',
            'Reserved' => 'bg-yellow-500',
            'Under_Maintenance' => 'bg-orange-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Get status label
     */
    private function getStatusLabel($status)
    {
        return match($status) {
            'Available' => 'Available',
            'Occupied' => 'Occupied',
            'Reserved' => 'Reserved',
            'Under_Maintenance' => 'Under Maintenance',
            default => ucfirst($status),
        };
    }
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex items-center justify-between rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
        <div>
            <flux:heading size="xl">{{ $unit->title }}</flux:heading>
            <flux:text class="mt-1 flex items-center gap-2">
                <span>Property Details</span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white {{ $this->getStatusColor($unit->status) }}">
                    {{ $this->getStatusLabel($unit->status) }}
                </span>
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('property.index')" variant="ghost" icon="arrow-left" size="sm">
                Back to Properties
            </flux:button>
            <flux:button :href="route('property.edit', $unit->id)" variant="primary" icon="pencil-square" size="sm">
                Edit
            </flux:button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Images Section -->
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">Images</flux:heading>
                
                @if($primaryImage)
                    <!-- Primary Image -->
                    <div class="mb-4 overflow-hidden rounded-lg">
                        <img src="{{ $primaryImage['image_url'] }}" 
                             alt="{{ $unit->title }}" 
                             class="h-[400px] w-full object-cover"
                             onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}';" />
                    </div>
                @endif

                <!-- Thumbnails -->
                @if(!empty($images) && count($images) > 1)
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6">
                        @foreach($images as $image)
                            <div class="relative cursor-pointer overflow-hidden rounded-lg border-2 {{ $image['is_primary'] ? 'border-blue-500' : 'border-transparent' }} hover:border-blue-300">
                                <img src="{{ $image['image_url'] }}" 
                                     alt="Property image" 
                                     class="h-20 w-full object-cover"
                                     onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}';" />
                                @if($image['is_primary'])
                                    <div class="absolute left-1 top-1 rounded bg-blue-600 px-1.5 py-0.5 text-[10px] text-white">
                                        Primary
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif(empty($images))
                    <div class="flex h-40 items-center justify-center rounded-lg border-2 border-dashed border-neutral-300">
                        <flux:text class="text-neutral-500">No images available</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <!-- Details Section -->
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">Property Details</flux:heading>

                <div class="space-y-4">
                    <!-- Amount -->
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Amount</flux:text>
                        <flux:text class="text-2xl font-bold text-blue-600">
                            ₦{{ number_format($unit->amount, 2) }}
                        </flux:text>
                    </div>

                    <hr class="border-neutral-200 dark:border-neutral-700" />

                    <!-- Basic Info -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Bedrooms</flux:text>
                            <flux:text class="text-sm font-semibold">{{ $unit->bedrooms ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Bathrooms</flux:text>
                            <flux:text class="text-sm font-semibold">{{ $unit->bathrooms ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Kitchens</flux:text>
                            <flux:text class="text-sm font-semibold">{{ $unit->kitchens ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Living Rooms</flux:text>
                            <flux:text class="text-sm font-semibold">{{ $unit->living_rooms ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Parking Spaces</flux:text>
                            <flux:text class="text-sm font-semibold">{{ $unit->parking_spaces ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Status</flux:text>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white {{ $this->getStatusColor($unit->status) }}">
                                {{ $this->getStatusLabel($unit->status) }}
                            </span>
                        </div>
                    </div>

                    <hr class="border-neutral-200 dark:border-neutral-700" />

                    <!-- Location -->
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Location</flux:text>
                        <div class="mt-1 space-y-1">
                            @if($compound)
                                <flux:text class="text-sm">
                                    <span class="font-medium">Compound:</span> {{ $compound->name ?? 'N/A' }}
                                </flux:text>
                            @endif
                            @if($property)
                                <flux:text class="text-sm">
                                    <span class="font-medium">Property Type:</span> {{ $property->name ?? 'N/A' }}
                                </flux:text>
                            @endif
                        </div>
                    </div>

                    <hr class="border-neutral-200 dark:border-neutral-700" />

                    <!-- Description -->
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Description</flux:text>
                        <flux:text class="mt-1 text-sm">
                            {{ $unit->description ?? 'No description provided.' }}
                        </flux:text>
                    </div>

                    <hr class="border-neutral-200 dark:border-neutral-700" />

                    <!-- Meta Info -->
                    <div class="space-y-1">
                        <flux:text class="text-xs text-neutral-500">
                            Created: {{ $unit->created_at ? $unit->created_at->format('M d, Y H:i') : 'N/A' }}
                        </flux:text>
                        @if($unit->created_by)
                            <flux:text class="text-xs text-neutral-500">
                                Created by: {{ $unit->createdBy?->name ?? 'Unknown' }}
                            </flux:text>
                        @endif
                        @if($unit->updated_at && $unit->updated_at != $unit->created_at)
                            <flux:text class="text-xs text-neutral-500">
                                Updated: {{ $unit->updated_at->format('M d, Y H:i') }}
                            </flux:text>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>