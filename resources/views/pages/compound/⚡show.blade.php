<?php

use Livewire\Component;
use App\Models\Compound;

new class extends Component
{
    public Compound $compound;

    public function mount(Compound $compound): void
    {
        $this->compound = $compound;
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-6 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" class="flex items-center gap-2">
                {{ $compound->name }}
                @if($compound->gated)
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        Gated
                    </span>
                @endif
            </flux:heading>
            
            <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                <span>{{ $compound->address }}</span>
                @if($compound->city || $compound->state)
                    <span>•</span>
                    <span>{{ implode(', ', array_filter([$compound->city, $compound->state])) }}</span>
                @endif
                @if($compound->zip_code)
                    <span>•</span>
                    <span>{{ $compound->zip_code }}</span>
                @endif
            </div>

            @if($compound->google_map_url)
                <div>
                    <a
                        href="{{ $compound->google_map_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline dark:text-blue-400"
                    >
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        View on Google Maps
                    </a>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('compound.index')" variant="ghost" icon="arrow-left" size="sm">
                Back to Compounds
            </flux:button>
            <flux:button :href="route('compound.edit', $compound)" variant="primary" icon="pencil-square" size="sm">
                Edit
            </flux:button>
        </div>
    </div>

    <!-- Flash Messages -->
    <x-flash-message />

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Properties</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ number_format($compound->total_properties) }}
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
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Units</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ number_format($compound->total_units) }}
                    </p>
                </div>
                <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Security</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ collect([$compound->security_guard, $compound->cctv, $compound->gated])->filter()->count() }}/3
                    </p>
                </div>
                <div class="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900/30">
                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Amenities</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ collect([$compound->playground, $compound->street_lights, $compound->fence_walled])->filter()->count() }}/3
                    </p>
                </div>
                <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Section -->
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 px-6 py-4 dark:border-neutral-700">
            <flux:heading size="lg">Details</flux:heading>
        </div>
        
        <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Address</h4>
                    <p class="mt-1 text-neutral-900 dark:text-white">{{ $compound->address }}</p>
                </div>

                @if($compound->landmark)
                    <div>
                        <h4 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Landmark</h4>
                        <p class="mt-1 text-neutral-900 dark:text-white">{{ $compound->landmark }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    @if($compound->city)
                        <div>
                            <h4 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">City</h4>
                            <p class="mt-1 text-neutral-900 dark:text-white">{{ $compound->city }}</p>
                        </div>
                    @endif
                    
                    @if($compound->state)
                        <div>
                            <h4 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">State</h4>
                            <p class="mt-1 text-neutral-900 dark:text-white">{{ $compound->state }}</p>
                        </div>
                    @endif
                </div>

                @if($compound->zip_code)
                    <div>
                        <h4 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Zip Code</h4>
                        <p class="mt-1 text-neutral-900 dark:text-white">{{ $compound->zip_code }}</p>
                    </div>
                @endif
            </div>

            <!-- Right Column -->
            <div>
                <h4 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Description</h4>
                <div class="mt-1 rounded-lg bg-neutral-50 p-4 dark:bg-neutral-800/50">
                    <p class="text-neutral-900 dark:text-white">{{ $compound->description }}</p>
                </div>

                @if($compound->latitude && $compound->longitude)
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Coordinates</h4>
                        <p class="mt-1 text-sm text-neutral-900 dark:text-white">
                            {{ $compound->latitude }}, {{ $compound->longitude }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Security & Amenities Section -->
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 px-6 py-4 dark:border-neutral-700">
            <flux:heading size="lg">Security & Amenities</flux:heading>
        </div>

        <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
            <!-- Security Features -->
            <div>
                <h4 class="mb-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">Security Features</h4>
                <div class="space-y-2">
                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-2 dark:border-neutral-700">
                        <span class="text-sm text-neutral-700 dark:text-neutral-300">Gated Community</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $compound->gated ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $compound->gated ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-2 dark:border-neutral-700">
                        <span class="text-sm text-neutral-700 dark:text-neutral-300">Security Guard</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $compound->security_guard ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $compound->security_guard ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-2 dark:border-neutral-700">
                        <span class="text-sm text-neutral-700 dark:text-neutral-300">CCTV</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $compound->cctv ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $compound->cctv ? 'Yes' : 'No' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Amenities -->
            <div>
                <h4 class="mb-3 text-sm font-medium text-neutral-500 dark:text-neutral-400">Amenities</h4>
                <div class="space-y-2">
                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-2 dark:border-neutral-700">
                        <span class="text-sm text-neutral-700 dark:text-neutral-300">Fence/Walled</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $compound->fence_walled ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $compound->fence_walled ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-2 dark:border-neutral-700">
                        <span class="text-sm text-neutral-700 dark:text-neutral-300">Street Lights</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $compound->street_lights ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $compound->street_lights ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-2 dark:border-neutral-700">
                        <span class="text-sm text-neutral-700 dark:text-neutral-300">Playground</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $compound->playground ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ $compound->playground ? 'Yes' : 'No' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>