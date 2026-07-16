<?php

namespace App\Support;

use App\Enums\BlockType;
use App\Enums\ContentStatus;
use App\Enums\PageTemplate;

class CmsOptions
{
    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            ContentStatus::Draft->value => 'Brouillon',
            ContentStatus::InReview->value => 'À valider',
            ContentStatus::Approved->value => 'Validé',
            ContentStatus::Scheduled->value => 'Programmé',
            ContentStatus::Published->value => 'Publié',
            ContentStatus::Hidden->value => 'Masqué',
            ContentStatus::Archived->value => 'Archivé',
        ];
    }

    /** @return array<string, string> */
    public static function templates(): array
    {
        return [
            PageTemplate::Editorial->value => 'Éditorial',
            PageTemplate::Gallery->value => 'Galerie',
            PageTemplate::Offer->value => 'Offre professionnelle',
            PageTemplate::Contact->value => 'Contact',
            PageTemplate::Simple->value => 'Page simple',
        ];
    }

    /** @return array<string, string> */
    public static function blockTypes(): array
    {
        return [
            BlockType::Hero->value => 'Bannière',
            BlockType::TextImage->value => 'Texte et image',
            BlockType::RichText->value => 'Texte riche',
            BlockType::Cards->value => 'Cartes',
            BlockType::Gallery->value => 'Galerie',
            BlockType::KeyFigures->value => 'Chiffres clés',
            BlockType::Quote->value => 'Citation',
            BlockType::CallToAction->value => 'Appel à l’action',
            BlockType::Faq->value => 'FAQ',
            BlockType::Reviews->value => 'Avis',
            BlockType::Spacer->value => 'Espacement',
            BlockType::Form->value => 'Formulaire',
        ];
    }
}
