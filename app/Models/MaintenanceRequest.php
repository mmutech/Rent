<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'unit_id',
        'user_id',
        'request_type',
        'description',
        'priority',
        'status',
        'request_date',
        'completion_date',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Generate a unique maintenance request number
    public static function generateRequestNumber(): string
    {
        do {
            $requestNumber = 'MR-' . strtoupper(uniqid());
        } while (self::where('request_number', $requestNumber)->exists());

        return $requestNumber;
    }
}
