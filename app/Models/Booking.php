<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
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
        'booking_reference',
        'user_id',
        'property_id',
        'agent_id',
        'start_date',
        'end_date',
        'total_price',
        'notes',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

     /**
     * Get the payments associated with the booking.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the invoice associated with the booking.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Get the lease/contract associated with the booking.
     */
    public function lease(): HasOne
    {
        return $this->hasOne(Lease::class);
    }

    // Generate a unique booking reference 4 characters long
    public static function generateBookingReference()
    {
        do {
            $reference = 'BR-' . strtoupper(uniqid());
        } while (self::where('booking_reference', $reference)->exists());

        return $reference;
    }
}
