<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Renewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'renewal_number',
        'user_id',
        'booking_id',
        'old_end_date',
        'new_end_date',
        'new_rent_amount',
        'status',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // Generate a unique renewal number
    public static function generateRenewalNumber(): string
    {
        do {
            $renewalNumber = 'RN' . strtoupper(uniqid());
        } while (self::where('renewal_number', $renewalNumber)->exists());

        return $renewalNumber;
    }
}
