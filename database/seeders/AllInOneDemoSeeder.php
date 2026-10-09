<?php

namespace Database\Seeders;

use App\Http\Controllers\DemoDataController;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StoreSetting;
use App\Models\Warehouse;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Loads the richest demo the shipped codebase supports (not the vendor's
 * separate demo.getstocky.com databases). Safe to re-run: skips blocks that
 * already exist.
 *
 *   php artisan db:seed --class=AllInOneDemoSeeder
 */
class AllInOneDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->enableAllModules();
        $this->enablePharmacyMode();
        $this->ensureStoreReady();

        $this->call(DemoDataSeeder::class);
        $this->call(ElectronicsDemoSeeder::class);
        $this->call(GroceryDemoSeeder::class);
        $this->call(ToysDemoSeeder::class);
        $this->call(RealEstateDemoSeeder::class);

        $this->generateTaggedDemoRecords();
        $this->seedPharmacySamples();
        $this->seedHospitalSamples();
        $this->seedSchoolSamples();
        $this->seedServiceSamples();
        $this->seedFleetSamples();

        // Grocery theme is the closest "super market" preset in the package.
        $this->activateGroceryTheme();

        $this->command?->info('All-in-one demo seed completed.');
    }

    private function enableAllModules(): void
    {
        $setting = Setting::first();
        if (! $setting) {
            return;
        }

        $keys = [
            'Store', 'hrm', 'recruit', 'meeting', 'marketing', 'accounting', 'EWallet',
            'commissions', 'promotions', 'woocommerce_settings', 'shopify', 'salla', 'jumia',
            'documents', 'subscription_product', 'manufacturing', 'assets', 'projects',
            'bookings', 'service', 'fleet', 'hospital', 'school',
        ];

        $flags = [];
        foreach ($keys as $key) {
            $flags[$key] = true;
        }

        if (Schema::hasColumn('settings', 'module_flags')) {
            $setting->module_flags = json_encode($flags);
        }
        $setting->save();
    }

    private function enablePharmacyMode(): void
    {
        if (! Schema::hasColumn('settings', 'pharmacy_mode')) {
            return;
        }
        Setting::query()->update(['pharmacy_mode' => 1]);
    }

    private function ensureStoreReady(): void
    {
        $this->call(StoreSettingSeeder::class);
        StoreSetting::query()->update(['enabled' => 1]);
    }

    private function generateTaggedDemoRecords(): void
    {
        $payload = [
            'products' => 300,
            'clients' => 100,
            'providers' => 80,
            'sales' => 300,
            'purchases' => 200,
            'quotations' => 80,
            'expenses' => 100,
        ];

        $user = User::query()->find(1);
        if (! $user) {
            $this->command?->warn('Admin user missing — skipped tagged demo generator.');

            return;
        }

        Auth::login($user);
        $request = Request::create('/api/demo_data/generate', 'POST', $payload);
        $request->setUserResolver(static fn (?string $guard = null) => $guard === 'api' ? $user : $user);

        $response = app(DemoDataController::class)->generate($request);
        $this->command?->info('Tagged demo generator: '.$response->getContent());
    }

    private function seedPharmacySamples(): void
    {
        if (! Schema::hasColumn('products', 'is_batch_tracked') || ! Schema::hasTable('product_batches')) {
            return;
        }

        if (Product::where('code', 'like', 'PH-DEMO-%')->exists()) {
            return;
        }

        $unitId = DB::table('units')->value('id') ?? 1;
        $categoryId = DB::table('categories')->value('id') ?? 1;
        $warehouseId = Warehouse::value('id') ?? 1;

        $medicines = [
            ['Amoxicillin 500mg', 'PH-DEMO-001', 30],
            ['Paracetamol 500mg', 'PH-DEMO-002', 60],
            ['Ibuprofen 400mg', 'PH-DEMO-003', 45],
            ['Vitamin C 1000mg', 'PH-DEMO-004', 90],
            ['Cough Syrup 120ml', 'PH-DEMO-005', 120],
        ];

        foreach ($medicines as [$name, $code, $shelfDays]) {
            $productId = DB::table('products')->insertGetId([
                'code' => $code,
                'Type_barcode' => 'CODE128',
                'name' => "[DEMO] $name",
                'cost' => 2.5,
                'price' => 6.99,
                'unit_id' => $unitId,
                'unit_sale_id' => $unitId,
                'unit_purchase_id' => $unitId,
                'category_id' => $categoryId,
                'is_variant' => 0,
                'tax_method' => 1,
                'type' => 'is_single',
                'is_active' => 1,
                'is_batch_tracked' => 1,
                'shelf_life_days' => $shelfDays,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('product_warehouse')->insert([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'qte' => 0,
                'manage_stock' => 1,
            ]);

            foreach (range(1, 2) as $batchNo) {
                $expiry = Carbon::now()->addDays(random_int(90, 400));
                DB::table('product_batches')->insert([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'batch_no' => $code.'-B'.$batchNo,
                    'expiry_date' => $expiry->format('Y-m-d'),
                    'qty' => random_int(20, 80),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedHospitalSamples(): void
    {
        if (! Schema::hasTable('patients') || DB::table('patients')->where('mrn', 'like', 'DEMO-%')->exists()) {
            return;
        }

        $deptId = DB::table('hospital_departments')->insertGetId([
            'name' => '[DEMO] General Medicine',
            'code' => 'DEMO-GM',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doctorId = DB::table('doctors')->insertGetId([
            'name' => '[DEMO] Dr. Amina Hassan',
            'code' => 'DEMO-D001',
            'department_id' => $deptId,
            'specialty' => 'Internal Medicine',
            'consultation_fee' => 45,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (range(1, 8) as $i) {
            $patientId = DB::table('patients')->insertGetId([
                'mrn' => 'DEMO-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name' => "[DEMO] Patient $i",
                'gender' => $i % 2 ? 'female' : 'male',
                'phone' => '0600000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('appointments')->insert([
                'reference' => 'DEMO-APT-'.$i,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'department_id' => $deptId,
                'scheduled_at' => now()->addDays($i)->setTime(10, 0),
                'status' => 'scheduled',
                'fee' => 45,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedSchoolSamples(): void
    {
        if (! Schema::hasTable('students') || DB::table('students')->where('admission_number', 'like', 'DEMO-%')->exists()) {
            return;
        }

        $yearId = DB::table('academic_years')->insertGetId([
            'name' => '[DEMO] 2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classId = DB::table('school_classes')->insertGetId([
            'name' => '[DEMO] Grade 8A',
            'code' => 'DEMO-8A',
            'level' => 8,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (range(1, 12) as $i) {
            $studentId = DB::table('students')->insertGetId([
                'admission_number' => 'DEMO-ST-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name' => "[DEMO] Student $i",
                'gender' => $i % 2 ? 'male' : 'female',
                'admission_date' => '2025-09-01',
                'guardian_name' => 'Guardian '.$i,
                'guardian_phone' => '0700000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('student_enrollments')->insert([
                'student_id' => $studentId,
                'academic_year_id' => $yearId,
                'class_id' => $classId,
                'roll_number' => (string) $i,
                'status' => 'active',
                'enrolled_on' => '2025-09-01',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedServiceSamples(): void
    {
        if (! Schema::hasTable('service_jobs') || DB::table('service_jobs')->where('Ref', 'like', 'DEMO-%')->exists()) {
            return;
        }

        $clientId = DB::table('clients')->value('id');
        if (! $clientId) {
            return;
        }

        foreach (range(1, 5) as $i) {
            DB::table('service_jobs')->insert([
                'Ref' => 'DEMO-SRV-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'client_id' => $clientId,
                'service_item' => "[DEMO] AC maintenance #$i",
                'job_type' => 'maintenance',
                'status' => $i % 2 ? 'in_progress' : 'pending',
                'scheduled_date' => now()->addDays($i)->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedFleetSamples(): void
    {
        if (! Schema::hasTable('vehicles') || DB::table('vehicles')->where('plate_number', 'like', 'DEMO-%')->exists()) {
            return;
        }

        foreach (range(1, 4) as $i) {
            DB::table('vehicles')->insert([
                'name' => "[DEMO] Delivery Van $i",
                'plate_number' => 'DEMO-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'make' => 'Toyota',
                'model' => 'HiAce',
                'year' => 2022,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function activateGroceryTheme(): void
    {
        $this->call(GroceryDemoSeeder::class);
    }
}
