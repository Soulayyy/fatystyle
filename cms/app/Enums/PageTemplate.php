<?php

namespace App\Enums;

enum PageTemplate: string
{
    case Editorial = 'editorial';
    case Gallery = 'gallery';
    case Offer = 'offer';
    case Contact = 'contact';
    case Simple = 'simple';
}
