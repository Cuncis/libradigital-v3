<?php

namespace App\Observers;

use App\Models\Guest;
use App\Notifications\NewRsvpReceived;

class GuestObserver
{
    public function created(Guest $guest): void
    {
        $guest->invitation->user->notify(new NewRsvpReceived($guest));
    }
}
