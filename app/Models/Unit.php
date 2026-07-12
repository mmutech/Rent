<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'compound_id',
        'property_id',
        'title',
        'description',
        'amount',
        'bedrooms',
        'bathrooms',
        'kitchens',
        'living_rooms',
        'parking_spaces',
        'status',
        'created_by',
        'updated_by'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function compound()
    {
        return $this->belongsTo(Compound::class, 'compound_id');
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class, 'property_id');
    }

    public function primaryImage()
    {
        return $this->hasOne(PropertyImage::class, 'property_id')->where('is_primary', true);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
