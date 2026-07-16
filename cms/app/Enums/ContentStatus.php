<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Hidden = 'hidden';
    case Archived = 'archived';
}
