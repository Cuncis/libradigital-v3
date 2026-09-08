<?php

namespace App;

enum CustomRequestStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case InProgress = 'in_progress';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
