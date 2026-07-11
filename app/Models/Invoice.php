<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'booking_id',
        'amount',
        'invoice_date',
        'due_date',
        'notes',
        'status',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // Generate a unique invoice number
    public static function generateInvoiceNumber(): string
    {
        do {
            $invoiceNumber = 'INV-' . strtoupper(uniqid());
        } while (self::where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }
}
