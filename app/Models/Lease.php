<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lease extends Model
{
    use HasFactory;

     /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Make sure this is correctly set up
        static::creating(function ($booking) {
            // Generate reference if not already set
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = self::generateBookingReference();
            }
            
            // Set default status if not provided
            if (empty($booking->status)) {
                $booking->status = 'Pending';
            }
        });
    }

    protected $fillable = [
        'booking_id',
        'reference_number',
        'lease_type',
        'terms_and_conditions',
        'signed_by_tenant',
        'signed_by_agent',
        'signed_by_landlord',
        'notes',
        'status',
        'payment_frequency',
        'created_by',
        'updated_by'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Generate a unique lease reference 4 characters long
    public static function generateLeaseReference()
    {
        do {
            $reference = 'LR-' . strtoupper(uniqid());
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }
}
