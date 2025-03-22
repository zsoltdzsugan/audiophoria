<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Track extends Model
{
    /** @use HasFactory<\Database\Factories\TrackFactory> */
    use HasFactory;

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    protected $guarded = ['id'];
}
