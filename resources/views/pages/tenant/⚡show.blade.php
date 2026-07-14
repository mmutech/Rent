<?php

use App\Models\User;
use App\Models\NextOfKin;
use App\Models\Property;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component
{
    use AuthorizesRequests;

    public $tenant;
    public $nextOfKin;
    public $booking;
    public $property;
    public $agent;
    public $tenantId;

    // Statistics
    public $totalBookings = 0;
    public $activeBookings = 0;
    public $completedBookings = 0;
    public $cancelledBookings = 0;

    public function mount($tenant)
    {
        $user = Auth::user();

        // Check permission
        if (!auth()->user()->hasPermissionTo('view-tenant')) {
            abort(403);
        }

        // Load tenant with relationships
        $this->tenant = User::with(['bookings', 'nextOfKin'])
            ->role('Tenant')
            ->findOrFail($tenant);
        
        $this->tenantId = $this->tenant->id;

        // Check authorization
        if ($user->id !== $this->tenant->created_by && !$user->hasRole('Admin')) {
            abort(403, 'You are not authorized to view this tenant.');
        }

        // Load next of kin
        $this->nextOfKin = $this->tenant->nextOfKin;

        // Load the most recent booking with relationships
        $this->booking = $this->tenant->bookings()
            ->with(['property', 'agent'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($this->booking) {
            $this->property = $this->booking->property;
            $this->agent = $this->booking->agent;
        }

        // Load statistics
        $this->loadStatistics();
    }

    /**
     * Load tenant statistics
     */
    private function loadStatistics(): void
    {
        $this->totalBookings = $this->tenant->bookings()->count();
        $this->activeBookings = $this->tenant->bookings()
            ->where('status', 'active')
            ->count();
        $this->completedBookings = $this->tenant->bookings()
            ->where('status', 'completed')
            ->count();
        $this->cancelledBookings = $this->tenant->bookings()
            ->where('status', 'cancelled')
            ->count();
    }

    /**
     * Get status badge color
     */
    public function getStatusColor($status): string
    {
        return match($status) {
            'Active' => 'bg-green-500',
            'Pending' => 'bg-yellow-500',
            'Confirmed' => 'bg-blue-500',
            'Completed' => 'bg-gray-500',
            'Cancelled' => 'bg-red-500',
            'Overdue' => 'bg-orange-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel($status): string
    {
        return match($status) {
            'Active' => 'Active',
            'Pending' => 'Pending',
            'Confirmed' => 'Confirmed',
            'Completed' => 'Completed',
            'Cancelled' => 'Cancelled',
            'Overdue' => 'Overdue',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Get tenant status color
     */
    public function getTenantStatusColor($isActive): string
    {
        return $isActive ? 'bg-green-500' : 'bg-red-500';
    }

    /**
     * Get tenant status label
     */
    public function getTenantStatusLabel($isActive): string
    {
        return $isActive ? 'Active' : 'Inactive';
    }

    /**
     * Get formatted price
     */
    public function getFormattedPrice($amount): string
    {
        return '₦' . number_format($amount, 2);
    }

    /**
     * Get profile image URL
     */
    public function getProfileImageUrl(): string
    {
        if ($this->tenant->profile_image) {
            return Storage::url($this->tenant->profile_image);
        }
        
        // Generate initials avatar
        $name = $this->tenant->name;
        $initials = collect(explode(' ', $name))
            ->map(fn($word) => strtoupper($word[0]))
            ->take(2)
            ->implode('');
        
        return "https://ui-avatars.com/api/?name={$initials}&background=4F46E5&color=ffffff&size=150";
    }

    /**
     * Format date for display
     */
    public function formatDate($date): string
    {
        if (!$date) {
            return 'N/A';
        }
        
        return $date instanceof \Carbon\Carbon 
            ? $date->format('M d, Y') 
            : \Carbon\Carbon::parse($date)->format('M d, Y');
    }

    /**
     * Format datetime for display
     */
    public function formatDateTime($date): string
    {
        if (!$date) {
            return 'N/A';
        }
        
        return $date instanceof \Carbon\Carbon 
            ? $date->format('M d, Y H:i') 
            : \Carbon\Carbon::parse($date)->format('M d, Y H:i');
    }

    /**
     * Calculate days remaining for booking
     */
    public function getDaysRemaining($endDate): ?int
    {
        if (!$endDate) {
            return null;
        }
        
        $end = $endDate instanceof \Carbon\Carbon 
            ? $endDate 
            : \Carbon\Carbon::parse($endDate);
        
        $today = now()->startOfDay();
        
        if ($end->lt($today)) {
            return 0;
        }
        
        return $today->diffInDays($end);
    }

    /**
     * Get booking duration in months
     */
    public function getBookingDuration($startDate, $endDate): string
    {
        if (!$startDate || !$endDate) {
            return 'N/A';
        }
        
        $start = $startDate instanceof \Carbon\Carbon 
            ? $startDate 
            : \Carbon\Carbon::parse($startDate);
        
        $end = $endDate instanceof \Carbon\Carbon 
            ? $endDate 
            : \Carbon\Carbon::parse($endDate);
        
        $months = $start->diffInMonths($end);
        
        if ($months > 0) {
            return $months . ' month' . ($months > 1 ? 's' : '');
        }
        
        $days = $start->diffInDays($end);
        return $days . ' day' . ($days > 1 ? 's' : '');
    }

    /**
     * Confirm delete action
     */
    public function confirmDelete(): void
    {
        // This will be handled by the parent component or modal
        $this->dispatch('confirm-delete', tenantId: $this->tenant->id);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <!-- Profile Image -->
            <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-full">
                <img src="{{ $this->getProfileImageUrl() }}" 
                     alt="{{ $tenant->name }}" 
                     class="h-full w-full object-cover"
                     onerror="this.onerror=null; this.src='{{ asset('images/default-avatar.png') }}';" />
            </div>
            
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl">{{ $tenant->name }}</flux:heading>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white {{ $this->getTenantStatusColor($tenant->is_active) }}">
                        {{ $this->getTenantStatusLabel($tenant->is_active) }}
                    </span>
                </div>
                <flux:text class="mt-1">
                    Tenant since {{ $this->formatDate($tenant->created_at) }}
                </flux:text>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('tenant.index')" variant="ghost" icon="arrow-left" size="sm">
                Back to Tenants
            </flux:button>
            <flux:button :href="route('tenant.edit', $tenant->id)" variant="primary" icon="pencil-square" size="sm">
                Edit
            </flux:button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Bookings</p>
                    <p class="mt-1 text-2xl font-semibold text-neutral-900 dark:text-white">
                        {{ $totalBookings }}
                    </p>
                </div>
                <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                    <flux:icon.book-open color="blue" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Active Bookings</p>
                    <p class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                        {{ $activeBookings }}
                    </p>
                </div>
                <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                    <flux:icon.check-circle color="green" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Completed</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-600 dark:text-gray-400">
                        {{ $completedBookings }}
                    </p>
                </div>
                <div class="rounded-full bg-gray-100 p-3 dark:bg-gray-900/30">
                    <flux:icon.check-badge color="gray" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Cancelled</p>
                    <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                        {{ $cancelledBookings }}
                    </p>
                </div>
                <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
                    <flux:icon.x-circle color="red" />
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Left Column - Tenant Info -->
        <div class="lg:col-span-2">
            <!-- Personal Information -->
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">Personal Information</flux:heading>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Full Name</flux:text>
                        <flux:text class="mt-1 font-medium">{{ $tenant->name }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Email</flux:text>
                        <flux:text class="mt-1">{{ $tenant->email }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Phone Number</flux:text>
                        <flux:text class="mt-1">{{ $tenant->phone ?? 'N/A' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">NIN</flux:text>
                        <flux:text class="mt-1">{{ $tenant->nin ?? 'N/A' }}</flux:text>
                    </div>
                    <div class="sm:col-span-2">
                        <flux:text class="text-sm font-medium text-neutral-500">Address</flux:text>
                        <flux:text class="mt-1">{{ $tenant->address ?? 'N/A' }}</flux:text>
                    </div>
                </div>

                <hr class="my-4 border-neutral-200 dark:border-neutral-700" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Created By</flux:text>
                        <flux:text class="mt-1">{{ $tenant->createdBy?->name ?? 'Unknown' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Created At</flux:text>
                        <flux:text class="mt-1">{{ $this->formatDateTime($tenant->created_at) }}</flux:text>
                    </div>
                    @if($tenant->updated_at && $tenant->updated_at != $tenant->created_at)
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Last Updated</flux:text>
                            <flux:text class="mt-1">{{ $this->formatDateTime($tenant->updated_at) }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Next of Kin Information -->
            @if($nextOfKin)
                <div class="mt-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-4">Next of Kin</flux:heading>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Full Name</flux:text>
                            <flux:text class="mt-1 font-medium">{{ $nextOfKin->name }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Email</flux:text>
                            <flux:text class="mt-1">{{ $nextOfKin->email }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Phone Number</flux:text>
                            <flux:text class="mt-1">{{ $nextOfKin->phone_number }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">NIN</flux:text>
                            <flux:text class="mt-1">{{ $nextOfKin->nin }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Relationship</flux:text>
                            <flux:text class="mt-1">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ $nextOfKin->relationship }}
                                </span>
                            </flux:text>
                        </div>
                        <div class="sm:col-span-2">
                            <flux:text class="text-sm font-medium text-neutral-500">Address</flux:text>
                            <flux:text class="mt-1">{{ $nextOfKin->address }}</flux:text>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column - Booking Information -->
        <div class="lg:col-span-1">
            @if($booking)
                <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Current Booking</flux:heading>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white {{ $this->getStatusColor($booking->status) }}">
                            {{ $this->getStatusLabel($booking->status) }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        <!-- Property Info -->
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Property</flux:text>
                            <flux:text class="mt-1 font-medium">{{ $property?->title ?? 'N/A' }}</flux:text>
                            @if($property)
                                <flux:text class="text-sm text-neutral-500">
                                    {{ $property->compound->name ?? 'Unknown Compound' }}
                                </flux:text>
                            @endif
                        </div>

                        <!-- Booking Reference -->
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Booking Reference</flux:text>
                            <flux:text class="mt-1 font-mono text-sm">
                                {{ $booking->booking_reference ?? 'N/A' }}
                            </flux:text>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <flux:text class="text-xs font-medium text-neutral-500">Start Date</flux:text>
                                <flux:text class="mt-1 text-sm font-medium">
                                    {{ $this->formatDate($booking->start_date) }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs font-medium text-neutral-500">End Date</flux:text>
                                <flux:text class="mt-1 text-sm font-medium">
                                    {{ $this->formatDate($booking->end_date) }}
                                </flux:text>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Duration</flux:text>
                            <flux:text class="mt-1">
                                {{ $this->getBookingDuration($booking->start_date, $booking->end_date) }}
                            </flux:text>
                        </div>

                        <!-- Days Remaining -->
                        @if($booking->status === 'Active')
                            <div>
                                <flux:text class="text-sm font-medium text-neutral-500">Days Remaining</flux:text>
                                <flux:text class="mt-1">
                                    @php
                                        $daysRemaining = $this->getDaysRemaining($booking->end_date);
                                    @endphp
                                    @if($daysRemaining === 0)
                                        <span class="text-red-600">Expired</span>
                                    @elseif($daysRemaining === null)
                                        N/A
                                    @else
                                        {{ $daysRemaining }} day{{ $daysRemaining > 1 ? 's' : '' }}
                                    @endif
                                </flux:text>
                            </div>
                        @endif

                        <!-- Price -->
                        <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                            <flux:text class="text-sm font-medium text-neutral-500">Total Price</flux:text>
                            <flux:text class="mt-1 text-xl font-bold text-blue-600">
                                {{ $this->getFormattedPrice($booking->total_price) }}
                            </flux:text>
                        </div>

                        <!-- Agent -->
                        @if($agent)
                            <div>
                                <flux:text class="text-sm font-medium text-neutral-500">Assigned Agent</flux:text>
                                <flux:text class="mt-1">{{ $agent->name }}</flux:text>
                                <flux:text class="text-sm text-neutral-500">{{ $agent->email }}</flux:text>
                            </div>
                        @endif

                        <!-- Booking Created -->
                        <div>
                            <flux:text class="text-xs text-neutral-500">
                                Booked on {{ $this->formatDateTime($booking->created_at) }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-4">Booking</flux:heading>
                    
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <svg class="h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <flux:text class="mt-2 text-neutral-500">
                            No bookings found for this tenant.
                        </flux:text>
                        <flux:button variant="primary" :href="route('booking.create')" size="sm" class="mt-2">
                            Create Booking
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Booking History -->
    @if($totalBookings > 1)
        <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="lg" class="mb-4">Booking History</flux:heading>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-800/50">
                        <tr class="text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Property</th>
                            <th class="px-4 py-3">Start Date</th>
                            <th class="px-4 py-3">End Date</th>
                            <th class="px-4 py-3">Total Price</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900/50">
                        @foreach($tenant->bookings()->orderBy('created_at', 'desc')->limit(10)->get() as $booking)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-mono">
                                    {{ $booking->booking_reference ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $booking->property?->title ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $this->formatDate($booking->start_date) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{ $this->formatDate($booking->end_date) }}
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">
                                    {{ $this->getFormattedPrice($booking->total_price) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white {{ $this->getStatusColor($booking->status) }}">
                                        {{ $this->getStatusLabel($booking->status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <flux:button size="xs" variant="ghost" icon="eye" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($totalBookings > 10)
                <div class="mt-4 text-center">
                    <flux:button variant="ghost" size="sm">
                        View All Bookings ({{ $totalBookings }})
                    </flux:button>
                </div>
            @endif
        </div>
    @endif
</div>