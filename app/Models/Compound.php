<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compound extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'description',
        'fence_walled',
        'gated',
        'security_guard',
        'cctv',
        'street_lights',
        'playground',
        'total_properties',
        'total_units',
        'latitude',
        'longitude',
        'google_map_url',
        'landmark',
        'city',
        'state',
        'zip_code',
        'is_active',
        'created_by',
        'updated_by'
    ];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}
