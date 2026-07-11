<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_reference',
        'user_id',
        'property_id',
        'agent_id',
        'start_date',
        'end_date',
        'total_price',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
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
