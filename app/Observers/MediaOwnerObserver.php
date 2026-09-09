<?php

namespace App\Observers;

use App\Models\Media;
use Illuminate\Support\Facades\Auth;

class MediaOwnerObserver
{
    public function creating(Media $media): void
    {
        if (blank($media->user_id) && Auth::check()) {
            $media->user_id = Auth::id();
        }
    }
}
