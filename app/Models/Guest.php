<?php

namespace App\Models;

use App\RsvpStatus;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'name',
        'attending',
        'party_size',
        'message',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'attending' => RsvpStatus::class,
            'party_size' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
