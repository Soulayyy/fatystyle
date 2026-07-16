<?php

namespace App\Enums;

enum ContactStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Replied = 'replied';
    case Closed = 'closed';
    case Spam = 'spam';
}
