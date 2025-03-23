<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function media(): HasOneThrough
    {
        return $this->hasOneThrough(Media::class, Track::class, 'id', 'id', 'track_id', 'media_id');
    }

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
