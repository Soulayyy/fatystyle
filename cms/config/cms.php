<?php

return [
    'default_locale' => env('CMS_DEFAULT_LOCALE', 'fr'),

    'supported_locales' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CMS_SUPPORTED_LOCALES', 'fr')),
    ))),

    'trash_retention_days' => (int) env('CMS_TRASH_RETENTION_DAYS', 30),
    'version_retention_months' => (int) env('CMS_VERSION_RETENTION_MONTHS', 12),
    'version_retention_count' => (int) env('CMS_VERSION_RETENTION_COUNT', 100),
    'mfa_required' => (bool) env('CMS_MFA_REQUIRED', true),
    'contact_recipient' => env('CMS_CONTACT_RECIPIENT', 'fatystyle@hotmail.fr'),
    'contact_delivery' => env('CMS_CONTACT_DELIVERY', 'mail'),
    'contact_retention_months' => (int) env('CMS_CONTACT_RETENTION_MONTHS', 36),
    'backup_retention_days' => (int) env('CMS_BACKUP_RETENTION_DAYS', 30),

    'media' => [
        'max_upload_mb' => (int) env('CMS_MEDIA_MAX_UPLOAD_MB', 20),
        'max_pixels' => (int) env('CMS_MEDIA_MAX_PIXELS', 60000000),
        'variant_widths' => [320, 640, 960, 1280, 1920],
        'webp_quality' => (int) env('CMS_MEDIA_WEBP_QUALITY', 82),
    ],

    'public_site_url' => env('CMS_PUBLIC_SITE_URL', 'http://localhost:8082'),
    'public_release_path' => env('CMS_PUBLIC_RELEASE_PATH', '../public-site/releases'),
    'public_content_link' => env('CMS_PUBLIC_CONTENT_LINK'),
    'public_media_link' => env('CMS_PUBLIC_MEDIA_LINK'),
    'release_keep_count' => (int) env('CMS_RELEASE_KEEP_COUNT', 20),
];
