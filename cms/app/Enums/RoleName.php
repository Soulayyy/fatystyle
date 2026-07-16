<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdministrator = 'super-administrator';
    case ContentAdministrator = 'content-administrator';
    case Editor = 'editor';
    case Validator = 'validator';
    case Auditor = 'auditor';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
