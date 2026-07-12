<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
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

    public function category()
    {
        return $this->belongsTo(Property::class, 'category_id');
    }
}
