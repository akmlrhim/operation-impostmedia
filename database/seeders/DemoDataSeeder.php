<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\BillingCycle;
use App\Enums\ClientStatus;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Payment;
use App\Models\ServicePackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DemoDataSeeder extends Seeder
{
    /**
     * Kunci yang tidak selalu diisi tiap baris contoh, supaya bentuk barisnya
     * seragam sebelum dipakai.
     *
     * @var array<string, mixed>
     */
    private const LEAD_DEFAULTS = [
        'email' => null,
        'pic_impost' => null,
        'next_action_date' => null,
        'next_action' => null,
        'folder_url' => null,
        'estimated_value' => null,
        'status' => LeadStatus::Open,
        'lost_reason' => null,
    ];

    public function run(): void
    {
        $this->call(CrmMasterDataSeeder::class);

        $this->seedLeads();
        $this->seedClients();
    }

    private function seedLeads(): void
    {
        $today = Carbon::today();

        $leads = [
            [
                'stage' => 'prospek-baru',
                'temperature' => LeadTemperature::Cold,
                'company_name' => 'CV Rasa Nusantara',
                'industry' => 'Makanan & Minuman',
                'contact_name' => 'Bu Marlina',
                'phone' => '0811-5100-221',
                'email' => 'marlina@rasanusantara.test',
                'region' => 'Banjarmasin',
                'source' => LeadSource::SocialMedia,
                'pic' => 'Rahim',
                'pic_impost' => 'Sari',
                'packages' => [['Pendampingan Legalitas UMKM', 'Paket NIB']],
                'date_in' => $today->copy()->subDays(4),
                'last_contact_date' => $today->copy()->subDays(4),
                'next_action_date' => $today->copy()->addDays(3),
                'next_action' => 'Kirim daftar dokumen NIB',
                'notes' => 'Masuk dari DM Instagram. Belum tahu butuh apa saja.',
                'activities' => [
                    [ActivityType::WhatsApp, 'Balas DM Instagram', 'Tanya soal pengurusan NIB untuk usaha katering rumahan. Dikirimi profil layanan.', -4, true],
                ],
            ],
            [
                'stage' => 'qualify',
                'temperature' => LeadTemperature::Warm,
                'company_name' => 'PT Sinar Tani Kalimantan',
                'industry' => 'Agrikultur',
                'contact_name' => 'Pak Hendra',
                'phone' => '0812-5566-778',
                'email' => 'hendra@sinartani.test',
                'region' => 'Banjarbaru',
                'source' => LeadSource::Referral,
                'pic' => 'Rahim',
                'pic_impost' => 'Dimas',
                'packages' => [['Pembukuan Bulanan UMKM', 'Paket Dasar']],
                'date_in' => $today->copy()->subDays(12),
                'last_contact_date' => $today->copy()->subDays(2),
                'next_action_date' => $today->copy()->addDays(2),
                'next_action' => 'Kirim simulasi biaya pembukuan',
                'notes' => 'Referral dari klien lama. Pembukuan masih pakai buku tulis.',
                'activities' => [
                    [ActivityType::Call, 'Telepon perkenalan', 'Dijelaskan alur pembukuan bulanan. Beliau minta simulasi biaya dulu sebelum lanjut.', -12, true],
                    [ActivityType::Meeting, 'Meeting di kantor klien', "Hadir: Pak Hendra, bagian gudang.\n\nKondisi sekarang: catatan penjualan manual, stok tidak pernah dicocokkan.\nYang diminta: laporan laba rugi bulanan dan rekap stok.\nCatatan: mereka belum punya rekening usaha terpisah.", -2, true],
                    [ActivityType::FollowUp, 'Kirim simulasi biaya', 'Rencana kirim rincian biaya per bulan beserta contoh laporan.', 2, false],
                ],
            ],
            [
                'stage' => 'proposal',
                'temperature' => LeadTemperature::Warm,
                'company_name' => 'Kopi Borneo Roastery',
                'industry' => 'Kedai Kopi',
                'contact_name' => 'Dimas Prasetyo',
                'phone' => '0813-4477-990',
                'email' => 'dimas@kopiborneo.test',
                'region' => 'Banjarmasin',
                'source' => LeadSource::Event,
                'pic' => 'Sari',
                'pic_impost' => 'Rahim',
                'packages' => [['Identitas Visual Brand', 'Paket Logo'], ['Social Media Management', 'Silver']],
                'date_in' => $today->copy()->subDays(21),
                'last_contact_date' => $today->copy()->subDays(5),
                'next_action_date' => $today->copy()->subDays(1),
                'next_action' => 'Tagih keputusan proposal',
                'folder_url' => 'https://drive.google.com/drive/folders/demo-kopi-borneo',
                'notes' => 'Ketemu di bazar UMKM. Mau rebranding sekalian kelola sosial media.',
                'activities' => [
                    [ActivityType::Visit, 'Kunjungan ke roastery', "Lihat langsung tempat dan produknya.\n\nMereka punya 3 varian single origin lokal. Kemasan sekarang masih polos, cuma stiker.\nTertarik paket logo + guideline, tapi budget belum fix.", -21, true],
                    [ActivityType::Proposal, 'Kirim proposal', 'Proposal logo + Social Media Silver dikirim lewat email. Total penawaran Rp 4.000.000.', -5, true],
                    [ActivityType::FollowUp, 'Follow up keputusan', 'Belum ada kabar sejak proposal dikirim. Perlu ditelepon.', -1, false],
                ],
            ],
            [
                'stage' => 'negosiasi',
                'temperature' => LeadTemperature::Hot,
                'company_name' => 'PT Amanah Logistik',
                'industry' => 'Logistik',
                'contact_name' => 'Ibu Ratna',
                'phone' => '0815-2233-441',
                'email' => 'ratna@amanahlog.test',
                'region' => 'Banjarmasin',
                'source' => LeadSource::Tender,
                'pic' => 'Rahim',
                'pic_impost' => 'Sari',
                'packages' => [['Social Media Management', 'Gold']],
                'date_in' => $today->copy()->subDays(30),
                'last_contact_date' => $today->copy()->subDay(),
                'next_action_date' => $today->copy()->addDay(),
                'next_action' => 'Kirim revisi harga final',
                'folder_url' => 'https://drive.google.com/drive/folders/demo-amanah',
                'notes' => 'Sudah setuju paket Gold, minta potongan 10% untuk kontrak 12 bulan.',
                'estimated_value' => 4_500_000,
                'activities' => [
                    [ActivityType::Email, 'Balas undangan tender', 'Kirim company profile dan portofolio.', -30, true],
                    [ActivityType::Meeting, 'Presentasi ke manajemen', "Hadir: Ibu Ratna (Marketing), Pak Yusuf (Direktur).\n\nYang dibahas: rencana konten 6 bulan, target penambahan follower, dan pelaporan.\nDirektur setuju arah kontennya. Yang jadi ganjalan cuma harga.", -8, true],
                    [ActivityType::Call, 'Negosiasi harga', 'Minta diskon 10% kalau kontrak langsung 12 bulan. Sudah disetujui atasan, tinggal kirim revisi.', -1, true],
                    [ActivityType::FollowUp, 'Kirim revisi harga final', 'Revisi jadi Rp 4.500.000/bulan untuk 12 bulan.', 1, false],
                ],
            ],
            [
                'stage' => 'lost-deal',
                'temperature' => LeadTemperature::Cold,
                'company_name' => 'Toko Bangunan Jaya Makmur',
                'industry' => 'Material Bangunan',
                'contact_name' => 'Pak Anto',
                'phone' => '0817-8899-100',
                'region' => 'Martapura',
                'source' => LeadSource::ColdOutreach,
                'pic' => 'Dimas',
                'packages' => [['Identitas Visual Brand', 'Paket Logo']],
                'status' => LeadStatus::Lost,
                'lost_reason' => 'Budget dialihkan ke renovasi toko',
                'date_in' => $today->copy()->subDays(45),
                'last_contact_date' => $today->copy()->subDays(20),
                'notes' => 'Sempat tertarik, tapi prioritas tahun ini renovasi.',
                'activities' => [
                    [ActivityType::Call, 'Cold call pertama', 'Bersedia dikirimi penawaran logo.', -45, true],
                    [ActivityType::Note, 'Ditunda ke tahun depan', 'Pak Anto bilang dana dipakai renovasi toko dulu. Boleh dihubungi lagi tahun depan.', -20, true],
                ],
            ],
        ];

        foreach ($leads as $position => $source) {
            $packages = $this->packages($source['packages']);
            $row = [...self::LEAD_DEFAULTS, ...$source];
            $stage = LeadStage::query()->where('slug', $row['stage'])->first();

            if ($stage === null) {
                continue;
            }

            $lead = Lead::query()->updateOrCreate(
                ['company_name' => $row['company_name']],
                [
                    'lead_stage_id' => $stage->id,
                    'date_in' => $row['date_in'],
                    'industry' => $row['industry'],
                    'contact_name' => $row['contact_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'region' => $row['region'],
                    'source' => $row['source'],
                    'pic' => $row['pic'],
                    'pic_impost' => $row['pic_impost'],
                    'estimated_value' => $row['estimated_value'] ?? $packages->sum('price'),
                    'last_contact_date' => $row['last_contact_date'],
                    'next_action_date' => $row['next_action_date'],
                    'next_action' => $row['next_action'],
                    'temperature' => $row['temperature'],
                    'notes' => $row['notes'],
                    'folder_url' => $row['folder_url'],
                    'status' => $row['status'],
                    'lost_reason' => $row['lost_reason'],
                    'position' => $position,
                ],
            );

            $lead->servicePackages()->sync(
                $packages->values()
                    ->mapWithKeys(fn (ServicePackage $package, int $index): array => [
                        $package->id => ['position' => $index],
                    ])
                    ->all(),
            );

            $this->seedTimeline($lead, $source['activities'], $today);
        }
    }

    /**
     * @param  list<array{0: ActivityType, 1: string, 2: string, 3: int, 4: bool}>  $activities
     */
    private function seedTimeline(Lead $lead, array $activities, Carbon $today): void
    {
        foreach ($activities as [$type, $title, $description, $dayOffset, $done]) {
            $at = $today->copy()->addDays($dayOffset)->setTime(10, 0);

            $lead->activities()->updateOrCreate(
                ['title' => $title],
                [
                    'type' => $type,
                    'description' => $description,
                    'scheduled_at' => $at,
                    'completed_at' => $done ? $at : null,
                ],
            );
        }
    }

    private function seedClients(): void
    {
        $today = Carbon::today();

        $client = Client::query()->updateOrCreate(
            ['company_name' => 'PT Sari Bumi Selatan'],
            [
                'short_code' => 'SBS',
                'email' => 'admin@saribumi.test',
                'phone' => '0511-3355-770',
                'address' => 'Jl. A. Yani Km 7 No. 21',
                'city' => 'Banjarmasin',
                'contact_name' => 'Ibu Lestari',
                'contact_position' => 'Manajer Pemasaran',
                'status' => ClientStatus::Active,
                'notes' => 'Klien retainer sejak awal tahun. Pembayaran selalu tepat waktu.',
            ],
        );

        Client::query()->updateOrCreate(
            ['company_name' => 'UD Berkah Mandiri'],
            [
                'short_code' => 'BM',
                'email' => 'berkahmandiri@mail.test',
                'phone' => '0511-4466-881',
                'address' => 'Jl. Veteran No. 88',
                'city' => 'Banjarmasin',
                'contact_name' => 'Pak Sulaiman',
                'contact_position' => 'Pemilik',
                'status' => ClientStatus::Inactive,
                'notes' => 'Sempat pakai jasa legalitas, sekarang belum ada proyek berjalan.',
            ],
        );

        $package = $this->package('Social Media Management', 'Gold');

        if ($package === null) {
            return;
        }

        $contract = Contract::query()->updateOrCreate(
            ['number' => 'IM-MOU-0107-SBS-001'],
            [
                'client_id' => $client->id,
                'type' => ContractType::Mou,
                'title' => 'Pengelolaan Media Sosial 12 Bulan',
                'start_date' => $today->copy()->startOfYear(),
                'end_date' => $today->copy()->startOfYear()->addYear()->subDay(),
                'signing_place' => 'Banjarmasin',
                'signed_date' => $today->copy()->startOfYear(),
                'subtotal' => 5_000_000,
                'tax_percent' => 11,
                'tax_amount' => 550_000,
                'value' => 5_550_000,
                'billing_cycle' => BillingCycle::Monthly,
                'next_invoice_date' => $today->copy()->startOfMonth()->addMonth(),
                'first_party_name' => 'Ibu Lestari',
                'first_party_position' => 'Manajer Pemasaran',
                'status' => ContractStatus::Active,
            ],
        );

        $contract->items()->updateOrCreate(
            ['name' => 'Social Media Management - Gold'],
            [
                'service_package_id' => $package->id,
                'description' => 'Pengelolaan konten dan iklan media sosial bulanan.',
                'quantity' => 1,
                'unit' => 'bulan',
                'unit_price' => 5_000_000,
                'amount' => 5_000_000,
                'position' => 0,
            ],
        );

        $this->seedInvoices($client, $contract, $package, $today);
    }

    private function seedInvoices(Client $client, Contract $contract, ServicePackage $package, Carbon $today): void
    {
        $rows = [
            ['IM-INV-0107-SBS-001', -2, InvoiceStatus::Paid, 5_550_000],
            ['IM-INV-0107-SBS-002', -1, InvoiceStatus::PartiallyPaid, 2_000_000],
            ['IM-INV-0107-SBS-003', 0, InvoiceStatus::Sent, 0],
        ];

        foreach ($rows as [$number, $monthOffset, $status, $paid]) {
            $issued = $today->copy()->startOfMonth()->addMonths($monthOffset);

            $invoice = Invoice::query()->updateOrCreate(
                ['number' => $number],
                [
                    'client_id' => $client->id,
                    'contract_id' => $contract->id,
                    'type' => InvoiceType::Invoice,
                    'issue_date' => $issued,
                    'due_date' => $issued->copy()->addDays(14),
                    'period_start' => $issued,
                    'period_end' => $issued->copy()->endOfMonth(),
                    'subtotal' => 5_000_000,
                    'tax_percent' => 11,
                    'tax_amount' => 550_000,
                    'total' => 5_550_000,
                    'amount_paid' => $paid,
                    'balance_due' => 5_550_000 - $paid,
                    'status' => $status,
                    'paid_at' => $status === InvoiceStatus::Paid ? $issued->copy()->addDays(9) : null,
                    'billing_snapshot' => $client->billingSnapshot(),
                ],
            );

            $invoice->items()->updateOrCreate(
                ['name' => 'Social Media Management - Gold'],
                [
                    'service_package_id' => $package->id,
                    'description' => 'Periode '.$issued->translatedFormat('F Y'),
                    'quantity' => 1,
                    'unit' => 'bulan',
                    'unit_price' => 5_000_000,
                    'amount' => 5_000_000,
                    'position' => 0,
                ],
            );

            if ($paid > 0) {
                Payment::query()->updateOrCreate(
                    ['invoice_id' => $invoice->id, 'amount' => $paid],
                    [
                        'paid_at' => $issued->copy()->addDays(9),
                        'method' => PaymentMethod::Transfer,
                        'notes' => 'Transfer dari rekening perusahaan.',
                    ],
                );
            }
        }
    }

    /**
     * @param  list<array{0: string, 1: string}>  $wanted
     * @return Collection<int, ServicePackage>
     */
    private function packages(array $wanted): Collection
    {
        return collect($wanted)
            ->map(fn (array $pair): ?ServicePackage => $this->package($pair[0], $pair[1]))
            ->filter()
            ->values();
    }

    private function package(string $service, string $package): ?ServicePackage
    {
        return ServicePackage::query()
            ->where('name', $package)
            ->whereRelation('service', 'name', $service)
            ->first();
    }
}
