<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    /** @use HasFactory<\Database\Factories\MediaFactory> */
    use HasFactory;

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    protected $guarded = ['id'];
}
