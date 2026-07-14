<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

new class extends Component
{
    use AuthorizesRequests;

    public $booking;
    public $bookingId;
    public $tenant;
    public $property;
    public $agent;
    public $compound;

    // Statistics
    public $totalPayments = 0;
    public $totalPaid = 0;
    public $remainingBalance = 0;
    public $paymentStatus = 'Unpaid';

    public function mount(Booking $booking)
    {
        // Check permission
        if (!auth()->user()->hasPermissionTo('view-booking')) {
            abort(403);
        }

        $user = Auth::user();

        // Load booking with all relationships
        $this->booking = Booking::with([
            'user',
            'property',
            'property.compound',
            'property.category',
            'agent',
            'payments',
            'invoice',
            'lease',
        ])->findOrFail($booking->id);

        $this->bookingId = $this->booking->id;

        // Check authorization
        if ($user->id !== $this->booking->created_by && !$user->hasRole('Admin')) {
            abort(403, 'You are not authorized to view this booking.');
        }

        // Set related data
        $this->tenant = $this->booking->user;
        $this->property = $this->booking->property;
        $this->compound = $this->property?->compound;
        $this->agent = $this->booking->agent;

        // Load payment statistics
        $this->loadPaymentStats();
    }

    /**
     * Load payment statistics
     */
    private function loadPaymentStats(): void
    {
        $this->totalPayments = $this->booking->payments()->count();
        $this->totalPaid = $this->booking->payments()
            ->where('status', 'completed')
            ->sum('amount');
        
        $this->remainingBalance = $this->booking->total_price - $this->totalPaid;
        
        if ($this->remainingBalance <= 0) {
            $this->paymentStatus = 'Paid';
        } elseif ($this->totalPaid > 0) {
            $this->paymentStatus = 'Partial';
        } else {
            $this->paymentStatus = 'Unpaid';
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusColor($status): string
    {
        return match($status) {
            'Pending' => 'bg-yellow-500',
            'Confirmed' => 'bg-blue-500',
            'Cancelled' => 'bg-red-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel($status): string
    {
        return match($status) {
            'Pending' => 'Pending',
            'Confirmed' => 'Confirmed',
            'Cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Get payment status color
     */
    public function getPaymentStatusColor($status): string
    {
        return match($status) {
            'Paid' => 'bg-green-500',
            'Partial' => 'bg-yellow-500',
            'Unpaid' => 'bg-red-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Get formatted price
     */
    public function getFormattedPrice($amount): string
    {
        return '₦' . number_format($amount, 2);
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
     * Calculate days remaining
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
     * Get booking duration
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
     * Get progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->booking->total_price <= 0) {
            return 0;
        }
        
        return min(100, round(($this->totalPaid / $this->booking->total_price) * 100));
    }

    /**
     * Confirm delete action
     */
    public function confirmDelete(): void
    {
        $this->dispatch('confirm-delete', bookingId: $this->booking->id);
    }

    /**
     * Get tenant initials for avatar
     */
    public function getInitials($name): string
    {
        if (!$name) {
            return '?';
        }
        
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        return substr($initials, 0, 2);
    }

    /**
     * Get status icon
     */
    public function getStatusIcon($status): string
    {
        return match($status) {
            'Pending' => 'clock',
            'Confirmed' => 'check-badge',
            'Active' => 'check-circle',
            'Completed' => 'check',
            'Cancelled' => 'x-circle',
            'Overdue' => 'exclamation-triangle',
            default => 'question-mark-circle',
        };
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">Booking Details</flux:heading>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white {{ $this->getStatusColor($booking->status) }}">
                    <flux:icon :name="$this->getStatusIcon($booking->status)" class="mr-1 h-3 w-3" />
                    {{ $this->getStatusLabel($booking->status) }}
                </span>
            </div>
            <flux:text class="mt-1 font-mono text-sm">
                Reference: {{ $booking->booking_reference ?? 'N/A' }}
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="route('booking.index')" variant="ghost" icon="arrow-left" size="sm">
                Back to Bookings
            </flux:button>
            
            @can('update-booking')
                <flux:button :href="route('booking.edit', $booking->id)" variant="primary" icon="pencil-square" size="sm">
                    Edit
                </flux:button>
            @endcan
            
            @can('delete-booking')
                <flux:button variant="danger" icon="trash" size="sm" wire:click="confirmDelete">
                    Delete
                </flux:button>
            @endcan
        </div>
    </div>

    <!-- Flash Messages -->
    <x-flash-message />

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Price</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-400">
                        {{ $this->getFormattedPrice($booking->total_price) }}
                    </p>
                </div>
                <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                    <flux:icon.currency-dollar color="blue" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Paid</p>
                    <p class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                        {{ $this->getFormattedPrice($totalPaid) }}
                    </p>
                </div>
                <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                    <flux:icon.credit-card color="green" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Remaining Balance</p>
                    <p class="mt-1 text-2xl font-semibold {{ $remainingBalance > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">
                        {{ $this->getFormattedPrice($remainingBalance) }}
                    </p>
                </div>
                <div class="rounded-full bg-red-100 p-3 dark:bg-red-900/30">
                    <flux:icon.banknotes color="red" />
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Payment Status</p>
                    <p class="mt-1 text-2xl font-semibold">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white {{ $this->getPaymentStatusColor($paymentStatus) }}">
                            {{ $paymentStatus }}
                        </span>
                    </p>
                </div>
                <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                    <flux:icon.chart-pie color="purple" />
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Left Column - Booking Info -->
        <div class="lg:col-span-2">
            <!-- Booking Information -->
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">Booking Information</flux:heading>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Booking Reference</flux:text>
                        <flux:text class="mt-1 font-mono font-medium">{{ $booking->booking_reference ?? 'N/A' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Status</flux:text>
                        <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white {{ $this->getStatusColor($booking->status) }}">
                            {{ $this->getStatusLabel($booking->status) }}
                        </span>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Start Date</flux:text>
                        <flux:text class="mt-1 font-medium">{{ $this->formatDate($booking->start_date) }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">End Date</flux:text>
                        <flux:text class="mt-1 font-medium">{{ $this->formatDate($booking->end_date) }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Duration</flux:text>
                        <flux:text class="mt-1">{{ $this->getBookingDuration($booking->start_date, $booking->end_date) }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Total Price</flux:text>
                        <flux:text class="mt-1 text-lg font-bold text-blue-600">{{ $this->getFormattedPrice($booking->total_price) }}</flux:text>
                    </div>
                    <div class="sm:col-span-2">
                        <flux:text class="text-sm font-medium text-neutral-500">Notes</flux:text>
                        <flux:text class="mt-1">{{ $booking->notes ?? 'No notes provided.' }}</flux:text>
                    </div>
                </div>

                <hr class="my-4 border-neutral-200 dark:border-neutral-700" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Created By</flux:text>
                        <flux:text class="mt-1">{{ $booking->createdBy?->name ?? 'Unknown' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500">Created At</flux:text>
                        <flux:text class="mt-1">{{ $this->formatDateTime($booking->created_at) }}</flux:text>
                    </div>
                    @if($booking->updated_at && $booking->updated_at != $booking->created_at)
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500">Last Updated</flux:text>
                            <flux:text class="mt-1">{{ $this->formatDateTime($booking->updated_at) }}</flux:text>
                        </div>
                        @if($booking->updatedBy)
                            <div>
                                <flux:text class="text-sm font-medium text-neutral-500">Updated By</flux:text>
                                <flux:text class="mt-1">{{ $booking->updatedBy?->name ?? 'Unknown' }}</flux:text>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Payments Section -->
            <div class="mt-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Payments</flux:heading>
                    <flux:button variant="primary" icon="plus" size="sm">
                        Add Payment
                    </flux:button>
                </div>

                <!-- Payment Progress -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-neutral-500">Payment Progress</span>
                        <span class="font-medium">{{ $this->getProgressPercentage() }}%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                        <div class="h-full rounded-full bg-blue-600 transition-all duration-500" 
                             style="width: {{ $this->getProgressPercentage() }}%"></div>
                    </div>
                </div>

                @if($booking->payments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead class="bg-neutral-50 dark:bg-neutral-800/50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Amount</th>
                                    <th class="px-4 py-3">Method</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900/50">
                                @foreach($booking->payments as $payment)
                                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                                        <td class="px-4 py-3 text-sm">{{ $this->formatDate($payment->created_at) }}</td>
                                        <td class="px-4 py-3 text-sm font-medium">{{ $this->getFormattedPrice($payment->amount) }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $payment->payment_method ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                                {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                                {{ ucfirst($payment->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <flux:button size="xs" variant="ghost" icon="eye" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-neutral-50 dark:bg-neutral-800/50">
                                <tr class="font-medium">
                                    <td class="px-4 py-3 text-sm text-neutral-500">Total</td>
                                    <td class="px-4 py-3 text-sm">{{ $this->getFormattedPrice($totalPaid) }}</td>
                                    <td class="px-4 py-3" colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <flux:icon.credit-card class="h-12 w-12 text-neutral-400" />
                        <flux:text class="mt-2 text-neutral-500">No payments recorded yet.</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column - Related Info -->
        <div class="lg:col-span-1">
            <!-- Tenant Information -->
            <div class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">Tenant</flux:heading>

                @if($tenant)
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <span class="text-lg font-semibold">{{ $this->getInitials($tenant->name) }}</span>
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ $tenant->name }}</flux:text>
                            <flux:text class="text-sm text-neutral-500">{{ $tenant->email }}</flux:text>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Phone</flux:text>
                            <flux:text class="text-sm">{{ $tenant->phone ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">NIN</flux:text>
                            <flux:text class="text-sm">{{ $tenant->nin ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Status</flux:text>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tenant->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <flux:button variant="ghost" icon="eye" size="sm" :href="route('tenant.show', $tenant->id)" class="w-full">
                            View Tenant Profile
                        </flux:button>
                    </div>
                @else
                    <flux:text class="text-neutral-500">No tenant assigned.</flux:text>
                @endif
            </div>

            <!-- Property Information -->
            <div class="mt-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">Property</flux:heading>

                @if($property)
                    <div class="space-y-2">
                        <div>
                            <flux:text class="font-medium">{{ $property->title }}</flux:text>
                        </div>
                        @if($compound)
                            <div>
                                <flux:text class="text-xs font-medium text-neutral-500">Compound</flux:text>
                                <flux:text class="text-sm">{{ $compound->name }}</flux:text>
                            </div>
                        @endif
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Amount</flux:text>
                            <flux:text class="text-sm font-medium text-blue-600">{{ $this->getFormattedPrice($property->amount) }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs font-medium text-neutral-500">Status</flux:text>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                {{ $property->status === 'Available' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                                {{ $property->status }}
                            </span>
                        </div>
                        @if($property->category)
                            <div>
                                <flux:text class="text-xs font-medium text-neutral-500">Category</flux:text>
                                <flux:text class="text-sm">{{ $property->category->name }}</flux:text>
                            </div>
                        @endif
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <flux:text class="text-xs font-medium text-neutral-500">Bedrooms</flux:text>
                                <flux:text class="text-sm">{{ $property->bedrooms ?? 'N/A' }}</flux:text>
                            </div>
                            <div>
                                <flux:text class="text-xs font-medium text-neutral-500">Bathrooms</flux:text>
                                <flux:text class="text-sm">{{ $property->bathrooms ?? 'N/A' }}</flux:text>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <flux:button variant="ghost" icon="eye" size="sm" :href="route('property.show', $property->id)" class="w-full">
                            View Property Details
                        </flux:button>
                    </div>
                @else
                    <flux:text class="text-neutral-500">No property assigned.</flux:text>
                @endif
            </div>

            <!-- Agent Information -->
            @if($agent)
                <div class="mt-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <flux:heading size="lg" class="mb-4">Agent</flux:heading>

                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                            <span class="text-sm font-semibold">{{ $this->getInitials($agent->name) }}</span>
                        </div>
                        <div>
                            <flux:text class="font-medium">{{ $agent->name }}</flux:text>
                            <flux:text class="text-sm text-neutral-500">{{ $agent->email }}</flux:text>
                        </div>
                    </div>

                    @if($agent->phone)
                        <div class="mt-2">
                            <flux:text class="text-xs font-medium text-neutral-500">Phone</flux:text>
                            <flux:text class="text-sm">{{ $agent->phone }}</flux:text>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Actions -->
            <div class="mt-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">Actions</flux:heading>
                
                <div class="flex flex-col gap-2">
                    @if(in_array($booking->status, ['Pending', 'Confirmed']))
                        <flux:button variant="primary" icon="check" size="sm" class="w-full">
                            Confirm Booking
                        </flux:button>
                    @endif
                    
                    @if($booking->status === 'Active')
                        <flux:button variant="primary" icon="check-circle" size="sm" class="w-full">
                            Mark as Completed
                        </flux:button>
                    @endif
                    
                    @if(in_array($booking->status, ['Pending', 'Confirmed', 'Active']))
                        <flux:button variant="danger" icon="x-circle" size="sm" class="w-full">
                            Cancel Booking
                        </flux:button>
                    @endif
                    
                    <flux:button variant="primary" icon="printer" size="sm" class="w-full">
                        Print Booking
                    </flux:button>
                    
                    @can('update-booking')
                        <flux:button variant="primary" icon="pencil-square" size="sm" :href="route('booking.edit', $booking->id)" class="w-full">
                            Edit Booking
                        </flux:button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>