<?php

/*
|--------------------------------------------------------------------------
| G-TechX product branding (fallbacks when settings DB is empty)
|--------------------------------------------------------------------------
*/

return [
    'app_name' => env('BRAND_APP_NAME', 'G-TechX'),
    'app_name_display' => env('BRAND_APP_NAME_DISPLAY', 'G-TechX'),
    'company_name' => env('BRAND_COMPANY_NAME', 'G-TechX'),
    'developed_by' => env('BRAND_DEVELOPED_BY', 'G-TechX'),
    'pwa_description' => env('BRAND_PWA_DESCRIPTION', 'G-TechX business management'),
    'logo_file' => env('BRAND_LOGO', 'logo.png'),
    'favicon_file' => env('BRAND_FAVICON', 'favicon.ico'),

    /** When false, the app never contacts the original vendor update CDN. */
    'vendor_updates_enabled' => env('VENDOR_UPDATES_ENABLED', false),
];
