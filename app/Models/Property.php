<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'compound_id',
        'category_id',
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

    public function category()
    {
        return $this->belongsTo(PropertyCategory::class, 'category_id');
    }

    public function compound()
    {
        return $this->belongsTo(Compound::class, 'compound_id');
    }

    public function booking()
    {
        return $this->hasOne(Booking::class, 'property_id');
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
