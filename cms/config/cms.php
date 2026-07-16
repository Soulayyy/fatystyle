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

    'public_site_url' => env('CMS_PUBLIC_SITE_URL', 'http://localhost:8082'),
    'public_release_path' => env('CMS_PUBLIC_RELEASE_PATH', '../public-site/releases'),
    'public_content_link' => env('CMS_PUBLIC_CONTENT_LINK'),
    'release_keep_count' => (int) env('CMS_RELEASE_KEEP_COUNT', 20),
];
