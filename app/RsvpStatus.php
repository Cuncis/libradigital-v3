<?php

namespace App;

enum RsvpStatus: string
{
    case Attending = 'attending';
    case NotAttending = 'not_attending';
    case Maybe = 'maybe';
}
