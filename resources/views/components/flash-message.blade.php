@props([
    'duration' => 5000,
])

@php
    $progressColors = [
        'success' => 'bg-green-500 dark:bg-green-400',
        'error' => 'bg-red-500 dark:bg-red-400',
        'warning' => 'bg-yellow-500 dark:bg-yellow-400',
        'info' => 'bg-blue-500 dark:bg-blue-400',
    ];
@endphp

@foreach (['success', 'error', 'warning', 'info'] as $key)
    @if (session($key))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, {{ $duration }})"
            x-show="show"
            x-transition:enter.duration.300ms.opacity
            x-transition:leave.duration.300ms.opacity
            class="relative overflow-hidden rounded-lg mb-3 shadow-sm"
        >
            @if($key === 'success')
                <flux:callout variant="success">
                    <div class="flex items-start gap-2">
                        <span class="flex-1">{{ session($key) }}</span>
                        <button
                            @click="show = false"
                            type="button"
                            class="flex-shrink-0 -mt-1 -mr-1 rounded-md p-1 text-green-600 hover:bg-green-100/50 dark:text-green-400 dark:hover:bg-green-900/30"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </flux:callout>
            @elseif($key === 'error')
                <flux:callout variant="danger">
                    <div class="flex items-start gap-2">
                        <span class="flex-1">{{ session($key) }}</span>
                        <button
                            @click="show = false"
                            type="button"
                            class="flex-shrink-0 -mt-1 -mr-1 rounded-md p-1 text-red-600 hover:bg-red-100/50 dark:text-red-400 dark:hover:bg-red-900/30"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </flux:callout>
            @elseif($key === 'warning')
                <flux:callout variant="warning">
                    <div class="flex items-start gap-2">
                        <span class="flex-1">{{ session($key) }}</span>
                        <button
                            @click="show = false"
                            type="button"
                            class="flex-shrink-0 -mt-1 -mr-1 rounded-md p-1 text-yellow-600 hover:bg-yellow-100/50 dark:text-yellow-400 dark:hover:bg-yellow-900/30"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </flux:callout>
            @else
                <flux:callout variant="info">
                    <div class="flex items-start gap-2">
                        <span class="flex-1">{{ session($key) }}</span>
                        <button
                            @click="show = false"
                            type="button"
                            class="flex-shrink-0 -mt-1 -mr-1 rounded-md p-1 text-blue-600 hover:bg-blue-100/50 dark:text-blue-400 dark:hover:bg-blue-900/30"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </flux:callout>
            @endif

            <!-- Progress Bar -->
            <div class="absolute bottom-0 left-0 h-1 w-full bg-neutral-200/50 dark:bg-neutral-700/50">
                <div
                    class="h-full transition-all duration-100 ease-linear {{ $progressColors[$key] }}"
                    x-init="
                        $el.style.width = '100%';
                        setTimeout(() => {
                            $el.style.width = '0%';
                        }, 100);
                    "
                    x-effect="
                        if (!show) {
                            $el.style.width = '0%';
                        }
                    "
                    style="transition-duration: {{ $duration }}ms;"
                ></div>
            </div>
        </div>
    @endif
@endforeach