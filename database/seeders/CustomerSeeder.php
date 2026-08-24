<?php

namespace Database\Seeders;

use App\Enums\CustomerAddressType;
use App\Enums\CustomerLogistikOperatingHours;
use App\Enums\CustomerPaymentTerm;
use App\Enums\CustomerPaymentTermBasis;
use App\Enums\CustomerStatus;
use App\Enums\QualityCheckingMethod;
use App\Enums\QuantityCheckingMethod;
use App\Enums\SiteEnvironment;
use App\Enums\StorageType;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerDocument;
use App\Models\CustomerLogistik;
use App\Models\CustomerPayment;
use App\Services\CustomerCodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Data dummy buat testing lokal -- semua nama/kontak/email FIKTIF (Faker
 * locale id_ID), bukan disalin dari data live manapun. Polanya (nama
 * perusahaan konstruksi/tambang, customer_status mayoritas 'prospect',
 * banyak field opsional kosong) meniru distribusi data live yang sempat
 * dicek sebagai referensi, bukan datanya sendiri.
 *
 * Sengaja bukan idempotent (bikin customer baru tiap run, bukan
 * updateOrCreate) -- ditujukan buat dijalankan sekali di atas tabel kosong
 * pasca migrate:fresh, bukan re-run berulang. Butuh CabangSeeder,
 * CustomerDocumentTypeSeeder, dan akun per-role (marketing@mail.dev dkk)
 * sudah ada duluan.
 */
class CustomerSeeder extends Seeder
{
    private const TOTAL_CUSTOMERS = 15;

    private const OWNER_EMAILS = [
        'marketing@mail.dev',
        'key-account@mail.dev',
        'marketing-proenergi@mail.dev',
    ];

    private const INDUSTRIES = [
        'Tambang', 'Beton', 'Konstruksi', 'Material', 'Readymix', 'Precast', 'Infrastruktur', 'Logistik',
    ];

    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        $ownerIds = DB::table('users')->whereIn('email', self::OWNER_EMAILS)->pluck('id', 'email');
        $cabangIds = DB::table('cabangs')->pluck('id_cabang')->all();
        $documentTypeIds = DB::table('customer_document_types')->pluck('id_document_type', 'code');

        if ($ownerIds->isEmpty() || empty($cabangIds)) {
            $this->command?->error('CustomerSeeder butuh akun per-role (marketing@mail.dev dkk) dan CabangSeeder jalan duluan.');

            return;
        }

        for ($i = 1; $i <= self::TOTAL_CUSTOMERS; $i++) {
            $ownerEmail = $faker->randomElement(self::OWNER_EMAILS);
            $ownerName = $ownerIds->has($ownerEmail) ? Str::headline(explode('@', $ownerEmail)[0]) : 'Seeder';

            $companyName = $faker->randomElement(['PT', 'CV']).' '.$faker->company().' '.$faker->randomElement(self::INDUSTRIES);
            $status = $faker->randomElement([
                CustomerStatus::Prospect->value,
                CustomerStatus::Prospect->value,
                CustomerStatus::Prospect->value,
                CustomerStatus::Active->value,
            ]);

            $customer = Customer::create([
                'id_user' => $ownerIds[$ownerEmail] ?? null,
                'email' => $faker->unique()->companyEmail(),
                'phone' => $faker->numerify('08##########'),
                'customer_type' => $faker->randomElement(['Retail', 'Project']),
                'company_name' => $companyName,
                'customer_code' => CustomerCodeGenerator::generate(),
                'id_cabang' => $faker->randomElement($cabangIds),
                'customer_status' => $status,
                'created_at' => now(),
                'created_by' => $ownerName,
                'updated_at' => now(),
                'updated_by' => $ownerName,
            ]);

            CustomerAddress::create([
                'id_customer' => $customer->id_customer,
                'address_type' => CustomerAddressType::HeadOffice,
                'address_line' => $faker->address(),
                'is_primary' => true,
            ]);

            // Separuh customer dapat 1 PIC + dokumen wajib (NIB/NPWP), sisanya sengaja dibiarkan kosong
            // -- mayoritas data live juga begitu, berguna buat nguji tampilan "belum lengkap".
            if ($i % 2 === 0) {
                CustomerContact::create([
                    'id_customer' => $customer->id_customer,
                    'full_name' => $faker->name(),
                    'position' => $faker->jobTitle(),
                    'phone' => $faker->numerify('021-#######'),
                    'mobile' => $faker->numerify('08##########'),
                    'email' => $faker->safeEmail(),
                ]);

                foreach (['nib', 'npwp'] as $code) {
                    if (!$documentTypeIds->has($code)) {
                        continue;
                    }

                    CustomerDocument::create([
                        'id_customer' => $customer->id_customer,
                        'id_document_type' => $documentTypeIds[$code],
                        'document_number' => $faker->numerify('##.###.###.#-###.###'),
                        'file_path' => "seed/{$code}-{$customer->id_customer}.pdf",
                        'file_name' => strtoupper($code).'.pdf',
                        'uploaded_at' => now(),
                    ]);
                }
            }

            // Sepertiga customer dapat 1 dokumen bebas -- nguji pola document_name (dokumen tanpa id_document_type)
            if ($i % 3 === 0) {
                CustomerDocument::create([
                    'id_customer' => $customer->id_customer,
                    'id_document_type' => null,
                    'document_name' => $faker->randomElement(['Portofolio Perusahaan', 'Company Profile', 'Surat Referensi Bank']),
                    'file_path' => "seed/dokumen-bebas-{$customer->id_customer}.pdf",
                    'file_name' => 'Dokumen.pdf',
                    'uploaded_at' => now(),
                ]);
            }

            // Sebagian kecil dapat payment + logistik lengkap, buat nguji completeness penuh
            if ($i % 4 === 0) {
                CustomerPayment::create([
                    'id_customer' => $customer->id_customer,
                    'payment_term' => CustomerPaymentTerm::Credit,
                    'payment_term_days' => 30,
                    'payment_term_basis' => CustomerPaymentTermBasis::DaysAfterInvoiceReceived,
                    'invoice' => true,
                    'calculate_method' => 'per_ton',
                    'bank_name' => $faker->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI']),
                    'account_number' => $faker->numerify('##########'),
                ]);

                CustomerLogistik::create([
                    'id_customer' => $customer->id_customer,
                    'site_environment' => SiteEnvironment::Industrial,
                    'storage_type' => StorageType::Outdoor,
                    'operating_hours' => CustomerLogistikOperatingHours::StandardOfficeHours,
                    'quality_checking_method' => QualityCheckingMethod::LabTest,
                    'quantity_checking_method' => QuantityCheckingMethod::DeliveryOrderVerification,
                    'max_truck_capacity_min' => 8,
                    'max_truck_capacity_max' => 20,
                    'estimated_monthly_volume' => $faker->numberBetween(500, 5000),
                    'created_at' => now(),
                    'created_by' => $ownerName,
                    'updated_at' => now(),
                    'updated_by' => $ownerName,
                ]);
            }
        }

        // 1 customer dengan onboarding_token aktif -- buat nguji wizard Onboarding end-to-end
        Customer::create([
            'id_user' => $ownerIds['marketing@mail.dev'] ?? null,
            'email' => $faker->unique()->companyEmail(),
            'phone' => $faker->numerify('08##########'),
            'customer_type' => 'Retail',
            'company_name' => 'PT Contoh Onboarding Aktif',
            'customer_code' => CustomerCodeGenerator::generate(),
            'id_cabang' => $faker->randomElement($cabangIds),
            'customer_status' => CustomerStatus::Prospect->value,
            'onboarding_token' => Str::random(40),
            'token_expired_at' => now()->addHours(24),
            'created_at' => now(),
            'created_by' => 'Marketing',
            'updated_at' => now(),
            'updated_by' => 'Marketing',
        ]);
    }
}
