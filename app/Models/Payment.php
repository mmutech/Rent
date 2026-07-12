<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'booking_id',
        'user_id',
        'payment_method',
        'amount',
        'notes',
        'status',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Generate a unique reference number for the payment
    public static function generateReferenceNumber(): string
    {
        do {
            $referenceNo = 'PAY-' . strtoupper(uniqid());
        } while (self::where('reference_number', $referenceNo)->exists());
        
        return $referenceNo;
    }
}
