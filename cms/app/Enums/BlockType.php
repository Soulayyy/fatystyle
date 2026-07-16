<?php

namespace App\Enums;

enum BlockType: string
{
    case Hero = 'hero';
    case TextImage = 'text_image';
    case RichText = 'rich_text';
    case Cards = 'cards';
    case Gallery = 'gallery';
    case KeyFigures = 'key_figures';
    case Quote = 'quote';
    case CallToAction = 'call_to_action';
    case Faq = 'faq';
    case Reviews = 'reviews';
    case Spacer = 'spacer';
    case Form = 'form';
}
