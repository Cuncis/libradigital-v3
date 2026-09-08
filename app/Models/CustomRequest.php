<?php

namespace App\Models;

use App\CustomRequestStatus;
use Database\Factories\CustomRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomRequest extends Model
{
    /** @use HasFactory<CustomRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invitation_id',
        'assigned_admin_id',
        'event_type',
        'event_date',
        'style_notes',
        'budget',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'budget' => 'integer',
            'status' => CustomRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
