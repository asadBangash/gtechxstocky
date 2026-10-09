<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use App\Models\StoreSetting;
use Database\Seeders\ElectronicsDemoSeeder;
use Database\Seeders\GroceryDemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Applies one of the demo.getstocky.com "shop" presets on a single database.
 * All demo catalogs remain imported; each preset changes modules, pharmacy/kitchen
 * flags, and which storefront theme/catalog is active.
 */
class DemoShopPresetService
{
    public const SHOPS = [
        'all_in_one' => [
            'label' => 'All In One',
            'color' => '#22c55e',
        ],
        'pharmacy' => [
            'label' => 'Pharmacy',
            'color' => '#ec4899',
        ],
        'multi_service' => [
            'label' => 'Multi-Service Center',
            'color' => '#f97316',
        ],
        'electronics' => [
            'label' => 'Electronics & Mobile Shop',
            'color' => '#8b5cf6',
        ],
        'supermarket' => [
            'label' => 'Super Market',
            'color' => '#1e3a8a',
        ],
        'restaurant' => [
            'label' => 'Restaurant',
            'color' => '#ef4444',
        ],
    ];

    public function apply(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return session('demo_shop_preset');
        }

        if (! isset(self::SHOPS[$key])) {
            return null;
        }

        match ($key) {
            'all_in_one' => $this->applyAllInOne(),
            'pharmacy' => $this->applyPharmacy(),
            'multi_service' => $this->applyMultiService(),
            'electronics' => $this->applyElectronics(),
            'supermarket' => $this->applySupermarket(),
            'restaurant' => $this->applyRestaurant(),
        };

        session(['demo_shop_preset' => $key]);

        if (function_exists('store_url_config_clear')) {
            store_url_config_clear();
        }

        return $key;
    }

    public function isEnabled(): bool
    {
        if (filter_var(env('DEMO_SHOP_SELECTOR', 'true'), FILTER_VALIDATE_BOOLEAN) === false) {
            return false;
        }

        return app()->environment('local', 'demo', 'staging')
            || filter_var(env('DEMO_SHOP_SELECTOR_FORCE', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function applyAllInOne(): void
    {
        $this->seedIfMissing();
        $this->setModuleFlags([
            'Store' => true, 'hrm' => true, 'recruit' => true, 'meeting' => true,
            'marketing' => true, 'accounting' => true, 'EWallet' => true, 'commissions' => true,
            'promotions' => true, 'woocommerce_settings' => true, 'shopify' => true, 'salla' => true,
            'jumia' => true, 'documents' => true, 'subscription_product' => true, 'manufacturing' => true,
            'assets' => true, 'projects' => true, 'bookings' => true, 'service' => true,
            'fleet' => true, 'hospital' => true, 'school' => true,
        ]);

        $this->setPharmacyMode(true);
        $this->setKitchen(false);

        $store = StoreSetting::first();
        if ($store) {
            $store->forceFill([
                'enabled' => 1,
                'theme' => 'default',
                'store_name' => 'Stocky Demo — All In One',
            ])->save();
        }

        $this->setStoreCatalogVisibility(['EL-%', 'GR-%', 'TY-%', 'DEMO-%', 'PH-DEMO-%'], true);
    }

    private function applyPharmacy(): void
    {
        $this->seedIfMissing();
        $this->setModuleFlags([
            'Store' => true, 'hrm' => true, 'accounting' => true, 'assets' => true,
            'hospital' => false, 'school' => false, 'fleet' => false, 'service' => false,
            'projects' => false, 'manufacturing' => false,
        ]);
        $this->setPharmacyMode(true);
        $this->setKitchen(false);

        $store = StoreSetting::first();
        if ($store) {
            $store->forceFill([
                'enabled' => 1,
                'theme' => 'default',
                'store_name' => 'Pharmacy Demo',
            ])->save();
        }

        $this->setStoreCatalogVisibility(['PH-DEMO-%', 'DEMO-%'], true);
        $this->setStoreCatalogVisibility(['EL-%', 'GR-%', 'TY-%'], true);
    }

    private function applyMultiService(): void
    {
        $this->seedIfMissing();
        $this->setModuleFlags([
            'service' => true, 'hospital' => true, 'school' => true, 'fleet' => true,
            'bookings' => true, 'projects' => true, 'hrm' => true, 'accounting' => true,
            'Store' => true, 'marketing' => false, 'manufacturing' => false,
        ]);
        $this->setPharmacyMode(false);
        $this->setKitchen(false);

        $store = StoreSetting::first();
        if ($store) {
            $store->forceFill([
                'enabled' => 1,
                'theme' => 'default',
                'store_name' => 'Multi-Service Demo',
            ])->save();
        }

        $this->setStoreCatalogVisibility(['DEMO-%'], true);
    }

    private function applyElectronics(): void
    {
        $this->seedIfMissing();
        Artisan::call('db:seed', ['--class' => ElectronicsDemoSeeder::class, '--force' => true]);
        $this->setModuleFlags(['Store' => true, 'hrm' => true, 'accounting' => true]);
        $this->setPharmacyMode(false);
        $this->setKitchen(false);
    }

    private function applySupermarket(): void
    {
        $this->seedIfMissing();
        Artisan::call('db:seed', ['--class' => GroceryDemoSeeder::class, '--force' => true]);
        $this->setModuleFlags(['Store' => true, 'hrm' => true, 'accounting' => true, 'promotions' => true]);
        $this->setPharmacyMode(false);
        $this->setKitchen(false);
    }

    private function applyRestaurant(): void
    {
        $this->seedIfMissing();
        $this->setModuleFlags([
            'Store' => true, 'bookings' => true, 'hrm' => true, 'marketing' => true,
        ]);
        $this->setPharmacyMode(false);
        $this->setKitchen(true);

        $store = StoreSetting::first();
        if ($store) {
            $store->forceFill([
                'enabled' => 1,
                'theme' => 'default',
                'store_name' => 'Restaurant Demo',
            ])->save();
        }

        $this->setStoreCatalogVisibility(['DEMO-%', 'GR-%'], true);
    }

    private function seedIfMissing(): void
    {
        if (! Product::where('code', 'like', 'GR-%')->exists()) {
            Artisan::call('db:seed', ['--class' => GroceryDemoSeeder::class, '--force' => true]);
        }
        if (! Product::where('code', 'like', 'EL-%')->exists()) {
            Artisan::call('db:seed', ['--class' => ElectronicsDemoSeeder::class, '--force' => true]);
        }
    }

    private function setModuleFlags(array $flags): void
    {
        if (! Schema::hasColumn('settings', 'module_flags')) {
            return;
        }

        $allKeys = [
            'Store', 'hrm', 'recruit', 'meeting', 'marketing', 'accounting', 'EWallet',
            'commissions', 'promotions', 'woocommerce_settings', 'shopify', 'salla', 'jumia',
            'documents', 'subscription_product', 'manufacturing', 'assets', 'projects',
            'bookings', 'service', 'fleet', 'hospital', 'school',
        ];

        $map = [];
        foreach ($allKeys as $key) {
            $map[$key] = $flags[$key] ?? false;
        }

        Setting::query()->update(['module_flags' => json_encode($map)]);
    }

    private function setPharmacyMode(bool $on): void
    {
        if (Schema::hasColumn('settings', 'pharmacy_mode')) {
            Setting::query()->update(['pharmacy_mode' => $on ? 1 : 0]);
        }
    }

    private function setKitchen(bool $on): void
    {
        $payload = [];
        if (Schema::hasColumn('settings', 'enable_kitchen_display')) {
            $payload['enable_kitchen_display'] = $on ? 1 : 0;
        }
        if (Schema::hasColumn('settings', 'kitchen_stations') && $on) {
            $payload['kitchen_stations'] = json_encode([
                ['id' => 'grill', 'name' => 'Grill', 'category_ids' => []],
                ['id' => 'pastry', 'name' => 'Pastry', 'category_ids' => []],
            ]);
        }
        if ($payload !== []) {
            Setting::query()->update($payload);
        }
    }

    /** @param  list<string>  $codeLikePatterns */
    private function setStoreCatalogVisibility(array $codeLikePatterns, bool $visible): void
    {
        if (! Schema::hasColumn('products', 'hide_from_online_store')) {
            return;
        }

        $query = Product::query();
        $query->where(function ($q) use ($codeLikePatterns) {
            foreach ($codeLikePatterns as $i => $pattern) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $q->{$method}('code', 'like', $pattern);
            }
        });
        $query->update(['hide_from_online_store' => $visible ? 0 : 1]);
    }
}
