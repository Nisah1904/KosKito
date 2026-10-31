<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    protected $fillable = [
        'name', 'short_name', 'city', 'address',
        'latitude', 'longitude', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function approvedProperties()
    {
        return $this->hasMany(Property::class)->where('status', 'approved');
    }
}
