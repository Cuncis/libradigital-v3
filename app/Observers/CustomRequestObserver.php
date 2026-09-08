<?php

namespace App\Observers;

use App\Models\CustomRequest;
use App\Notifications\CustomRequestStatusChanged;

class CustomRequestObserver
{
    public function updated(CustomRequest $customRequest): void
    {
        if ($customRequest->wasChanged('status')) {
            $customRequest->user->notify(new CustomRequestStatusChanged($customRequest));
        }
    }
}
