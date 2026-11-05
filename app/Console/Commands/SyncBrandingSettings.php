<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * Apply G-TechX branding to the settings row (logo paths, app name, footer).
 * Safe on production after deploy: php artisan app:sync-branding
 */
class SyncBrandingSettings extends Command
{
    protected $signature = 'app:sync-branding {--force : Overwrite app name and logo even if already customized}';

    protected $description = 'Set G-TechX app name, footer, logo and favicon paths in settings';

    public function handle(): int
    {
        $setting = Setting::whereNull('deleted_at')->first();
        if (! $setting) {
            $this->warn('No settings row found. Run db:seed first.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $name = config('branding.app_name_display');
        $company = config('branding.company_name');
        $developed = config('branding.developed_by');
        $logo = config('branding.logo_file');
        $favicon = config('branding.favicon_file');
        $year = date('Y');

        $updates = [];

        if ($force || ! trim((string) ($setting->app_name ?? '')) || stripos($setting->app_name, 'stocky') !== false) {
            $updates['app_name'] = $name;
            $updates['page_title_suffix'] = '';
        }
        if ($force || stripos((string) ($setting->CompanyName ?? ''), 'stocky') !== false) {
            $updates['CompanyName'] = $company;
        }
        if ($force || stripos((string) ($setting->developed_by ?? ''), 'stocky') !== false) {
            $updates['developed_by'] = $developed;
        }
        if ($force || stripos((string) ($setting->footer ?? ''), 'stocky') !== false) {
            $updates['footer'] = "© {$year} {$name}. All rights reserved.";
        }
        if ($force || in_array($setting->logo, [null, '', 'logo-default.png', 'logo.png'], true)) {
            $updates['logo'] = $logo;
        }
        if ($force || in_array($setting->favicon, [null, '', 'favicon.ico'], true)) {
            $updates['favicon'] = $favicon;
        }
        if ($force || ! trim((string) ($setting->pwa_name ?? '')) || stripos($setting->pwa_name, 'stocky') !== false) {
            $updates['pwa_name'] = $name;
            $updates['pwa_short_name'] = $name;
        }
        if ($force || ! trim((string) ($setting->mobile_app_name ?? '')) || stripos($setting->mobile_app_name, 'stocky') !== false) {
            $updates['mobile_app_name'] = $name;
        }

        if ($updates === []) {
            $this->info('Settings already use custom branding. Use --force to overwrite.');

            return self::SUCCESS;
        }

        $setting->update($updates);
        $this->info('Updated settings: '.implode(', ', array_keys($updates)));

        return self::SUCCESS;
    }
}
