<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NextOfKin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'relationship',
        'phone_number',
        'email',
        'address',
        'identification_number',
        'identification_type',
        'is_verified',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Verify the next of kin's identification
    public function verifyIdentification()
    {
        $this->is_verified = true;
        $this->save();
        
        return $this;
    }
}
