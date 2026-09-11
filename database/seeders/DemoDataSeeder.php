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
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Payment;
use App\Models\ServicePackage;
use App\Models\User;
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
        'vacancy_position' => null,
        'next_action_date' => null,
        'next_action' => null,
        'meeting_date' => null,
        'folder_url' => null,
        'estimated_value' => null,
        'status' => LeadStatus::Open,
        'lost_reason' => null,
        'owner' => 'manager',
        'author' => 'admin',
    ];

    /**
     * @var array<string, User>
     */
    private array $team = [];

    public function run(): void
    {
        $this->call(CrmMasterDataSeeder::class);

        $this->team = $this->seedTeam();

        $this->seedLeads();
        $this->seedRetainerClient();
        $this->seedWaitingClient();
        $this->seedQuietClient();
    }

    /**
     * Rekan kerja contoh. Superuser yang sudah ada tidak diutak-atik, cuma
     * dipakai sebagai pemilik sebagian data supaya loncengnya terisi.
     *
     * @return array<string, User>
     */
    private function seedTeam(): array
    {
        $team = [
            'admin' => $this->member('Sari Wulandari', 'sari@impostmedia.test', UserRole::Administrator, true),
            'manager' => $this->member('Rina Kartika', 'rina@impostmedia.test', UserRole::Manager, true),
            'member' => $this->member('Bagas Nugroho', 'bagas@impostmedia.test', UserRole::Member, true),
            'pending' => $this->member('Tio Hermawan', 'tio@impostmedia.test', UserRole::Member, false),
        ];

        $team['owner'] = User::query()
            ->where('role', UserRole::Superuser)
            ->whereNotNull('approved_at')
            ->orderBy('id')
            ->first() ?? $team['admin'];

        return $team;
    }

    private function member(string $name, string $email, UserRole $role, bool $approved): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'role' => $role,
            'is_active' => true,
            'approved_at' => $approved ? $user->approved_at ?? now()->subMonths(6) : null,
            'email_verified_at' => $user->email_verified_at ?? now()->subMonths(6),
        ])->save();

        return $user;
    }

    /**
     * Nama pendek anggota tim. Nama yang tidak dikenal jatuh ke administrator,
     * supaya salah ketik di data contoh tidak menghentikan seeder.
     */
    private function person(mixed $key): int
    {
        $name = is_string($key) && array_key_exists($key, $this->team) ? $key : 'admin';

        return $this->team[$name]->id;
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
                'owner' => 'member',
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
                'stage' => 'prospek-baru',
                'temperature' => LeadTemperature::Warm,
                'company_name' => 'CV Griya Furnitur Banjar',
                'industry' => 'Furnitur',
                'vacancy_position' => 'Graphic Designer',
                'contact_name' => 'Pak Yusuf',
                'phone' => '0822-6644-330',
                'email' => 'yusuf@griyafurnitur.test',
                'region' => 'Banjarmasin',
                'source' => LeadSource::JobPosting,
                'pic' => 'Sari',
                'owner' => 'member',
                'packages' => [['Social Media Management', 'Silver']],
                'date_in' => $today->copy()->subDays(2),
                'last_contact_date' => $today->copy()->subDays(2),
                'meeting_date' => $today->copy()->addDays(4),
                'next_action_date' => $today->copy()->addDays(4),
                'next_action' => 'Meeting kenalan paket Social Media Management',
                'notes' => 'Ketemu lowongan Graphic Designer di loker online. Kemungkinan belum ada tim sosmed sendiri, ditawari paket kelola sosmed.',
                'activities' => [
                    [ActivityType::WhatsApp, 'Chat perkenalan lewat WA', 'Follow up dari lowongan Graphic Designer yang mereka pasang. Ditawari paket kelola sosmed, tertarik dan minta dijadwalkan meeting.', -2, true],
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
                'owner' => 'manager',
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
                'owner' => 'admin',
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
                'owner' => 'owner',
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
                'stage' => 'contact',
                'temperature' => LeadTemperature::Hot,
                'company_name' => 'Klinik Sehat Bersama',
                'industry' => 'Kesehatan',
                'contact_name' => 'dr. Ayu',
                'phone' => '0819-7788-220',
                'email' => 'ayu@kliniksehat.test',
                'region' => 'Banjarbaru',
                'source' => LeadSource::Website,
                'pic' => 'Sari',
                'owner' => 'member',
                'packages' => [['Identitas Visual Brand', 'Paket Brand Guideline']],
                'date_in' => $today->copy()->subDays(9),
                'last_contact_date' => $today->copy()->subDays(6),
                'next_action_date' => $today->copy()->subDays(3),
                'next_action' => 'Telepon ulang, dua kali tidak diangkat',
                'notes' => 'Isi formulir di website. Mau seragamkan tampilan klinik dan media sosialnya.',
                'activities' => [
                    [ActivityType::Email, 'Balas pengajuan dari website', 'Dikirimi ringkasan layanan branding beserta contoh pengerjaan sebelumnya.', -9, true],
                    [ActivityType::Call, 'Telepon perkenalan', 'Tidak diangkat, dijadwalkan ulang.', -6, true],
                    [ActivityType::FollowUp, 'Telepon ulang', 'Sudah lewat tiga hari dari jadwal, belum sempat dihubungi lagi.', -3, false],
                ],
            ],
            [
                'stage' => 'closed-won',
                'temperature' => LeadTemperature::Hot,
                'company_name' => 'PT Nusa Kreatif Digital',
                'industry' => 'Teknologi',
                'contact_name' => 'Pak Reza',
                'phone' => '0821-3344-556',
                'email' => 'reza@nusakreatif.test',
                'region' => 'Banjarmasin',
                'source' => LeadSource::Referral,
                'pic' => 'Rahim',
                'owner' => 'admin',
                'status' => LeadStatus::Won,
                'packages' => [['Pendampingan Legalitas UMKM', 'Paket Lengkap Legalitas']],
                'date_in' => $today->copy()->subDays(40),
                'last_contact_date' => $today->copy()->subDays(15),
                'notes' => 'Sudah tanda tangan MoU legalitas. Lanjut ke pengurusan izin edar.',
                'activities' => [
                    [ActivityType::Meeting, 'Pembahasan ruang lingkup', 'Sepakat paket lengkap legalitas beserta pendampingan izin edar.', -25, true],
                    [ActivityType::Note, 'MoU ditandatangani', 'Dokumen ditandatangani di kantor klien.', -15, true],
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
                'owner' => 'member',
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
                    'vacancy_position' => $row['vacancy_position'],
                    'contact_name' => $row['contact_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'region' => $row['region'],
                    'source' => $row['source'],
                    'pic' => $row['pic'],
                    'created_by' => $this->person($row['author']),
                    'estimated_value' => $row['estimated_value'] ?? $packages->sum('price'),
                    'last_contact_date' => $row['last_contact_date'],
                    'next_action_date' => $row['next_action_date'],
                    'next_action' => $row['next_action'],
                    'meeting_date' => $row['meeting_date'],
                    'temperature' => $row['temperature'],
                    'notes' => $row['notes'],
                    'folder_url' => $row['folder_url'],
                    'status' => $row['status'],
                    'lost_reason' => $row['lost_reason'],
                    'position' => $position,
                ],
            );

            $lead->assignees()->sync([$this->person($row['owner'])]);

            $lead->servicePackages()->sync(
                $packages->values()
                    ->mapWithKeys(fn (ServicePackage $package, int $index): array => [
                        $package->id => ['position' => $index],
                    ])
                    ->all(),
            );

            $this->seedTimeline($lead, $source['activities'], $today, $this->person($row['owner']));
        }
    }

    /**
     * @param  list<array{0: ActivityType, 1: string, 2: string, 3: int, 4: bool}>  $activities
     */
    private function seedTimeline(Lead $lead, array $activities, Carbon $today, int $userId): void
    {
        foreach ($activities as [$type, $title, $description, $dayOffset, $done]) {
            $at = $today->copy()->addDays($dayOffset)->setTime(10, 0);

            $lead->activities()->updateOrCreate(
                ['title' => $title],
                [
                    'type' => $type,
                    'description' => $description,
                    'user_id' => $userId,
                    'scheduled_at' => $at,
                    'completed_at' => $done ? $at : null,
                ],
            );
        }
    }

    /**
     * Klien retainer: MoU berjalan, tagihan bulanan, satu lunas satu kurang bayar.
     */
    private function seedRetainerClient(): void
    {
        $today = Carbon::today();

        $client = $this->client('PT Sari Bumi Selatan', [
            'short_code' => 'SBS',
            'email' => 'admin@saribumi.test',
            'phone' => '0511-3355-770',
            'address' => 'Jl. A. Yani Km 7 No. 21',
            'city' => 'Banjarmasin',
            'contact_name' => 'Ibu Lestari',
            'contact_position' => 'Manajer Pemasaran',
            'status' => ClientStatus::Active,
            'notes' => 'Klien retainer sejak awal tahun. Pembayaran selalu tepat waktu.',
            'assigned_to' => $this->person('manager'),
            'created_by' => $this->person('admin'),
        ]);

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
                'created_by' => $this->person('admin'),
            ],
        );

        $contract->assignees()->sync([$this->person('manager')]);

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

        $rows = [
            ['IM-INV-0107-SBS-001', -2, InvoiceStatus::Paid, 5_550_000],
            ['IM-INV-0107-SBS-002', -1, InvoiceStatus::PartiallyPaid, 2_000_000],
            ['IM-INV-0107-SBS-003', 0, InvoiceStatus::Sent, 0],
        ];

        foreach ($rows as [$number, $monthOffset, $status, $paid]) {
            $issued = $today->copy()->startOfMonth()->addMonths($monthOffset);

            $invoice = $this->invoice($number, [
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
                'assigned_to' => $this->person('manager'),
                'created_by' => $this->person('admin'),
            ]);

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
                        'recorded_by' => $this->person('admin'),
                    ],
                );
            }
        }
    }

    /**
     * Klien baru: MoU masih menunggu tanda tangan, satu invoice draf, dan satu
     * tagihan yang sudah lewat jatuh tempo.
     */
    private function seedWaitingClient(): void
    {
        $today = Carbon::today();

        $client = $this->client('PT Nusa Kreatif Digital', [
            'short_code' => 'NKD',
            'email' => 'reza@nusakreatif.test',
            'phone' => '0821-3344-556',
            'address' => 'Jl. Pangeran Antasari No. 45',
            'city' => 'Banjarmasin',
            'contact_name' => 'Pak Reza',
            'contact_position' => 'Direktur',
            'status' => ClientStatus::Active,
            'notes' => 'Hasil konversi lead. Sedang diurus legalitas dan izin edarnya.',
            'assigned_to' => $this->person('owner'),
            'created_by' => $this->person('admin'),
        ]);

        Lead::query()
            ->where('company_name', 'PT Nusa Kreatif Digital')
            ->update(['converted_client_id' => $client->id]);

        $package = $this->package('Pendampingan Legalitas UMKM', 'Paket Lengkap Legalitas');

        if ($package === null) {
            return;
        }

        $contract = Contract::query()->updateOrCreate(
            ['number' => 'IM-MOU-0109-NKD-001'],
            [
                'client_id' => $client->id,
                'type' => ContractType::Mou,
                'title' => 'Pendampingan Legalitas dan Izin Edar',
                'start_date' => $today->copy()->addDays(7),
                'end_date' => $today->copy()->addDays(24),
                'signing_place' => 'Banjarmasin',
                'signed_date' => $today->copy()->addDays(3),
                'subtotal' => 2_500_000,
                'tax_percent' => 11,
                'tax_amount' => 275_000,
                'value' => 2_775_000,
                'billing_cycle' => BillingCycle::OneTime,
                'first_party_name' => 'Pak Reza',
                'first_party_position' => 'Direktur',
                'status' => ContractStatus::Sent,
                'created_by' => $this->person('admin'),
            ],
        );

        $contract->assignees()->sync([$this->person('owner')]);

        $contract->items()->updateOrCreate(
            ['name' => 'Pendampingan Legalitas UMKM - Paket Lengkap Legalitas'],
            [
                'service_package_id' => $package->id,
                'description' => 'Pengurusan NIB, sertifikasi halal, dan izin edar.',
                'quantity' => 1,
                'unit' => 'paket',
                'unit_price' => 2_500_000,
                'amount' => 2_500_000,
                'position' => 0,
            ],
        );

        $overdue = $today->copy()->subDays(35);

        $late = $this->invoice('IM-INV-0108-NKD-001', [
            'client_id' => $client->id,
            'type' => InvoiceType::Invoice,
            'issue_date' => $overdue,
            'due_date' => $overdue->copy()->addDays(14),
            'subtotal' => 750_000,
            'tax_percent' => 11,
            'tax_amount' => 82_500,
            'total' => 832_500,
            'amount_paid' => 0,
            'balance_due' => 832_500,
            'status' => InvoiceStatus::Sent,
            'notes' => 'Sudah dikirim, statusnya belum sempat diperbarui walau tanggalnya lewat.',
            'billing_snapshot' => $client->billingSnapshot(),
            'assigned_to' => $this->person('owner'),
            'created_by' => $this->person('member'),
        ]);

        $this->singleItem($late, $this->package('Pendampingan Legalitas UMKM', 'Paket NIB'), 'Paket NIB', 750_000);

        $draft = $this->invoice('IM-INV-0109-NKD-002', [
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'type' => InvoiceType::Invoice,
            'issue_date' => $today,
            'due_date' => $today->copy()->addDays(14),
            'subtotal' => 2_500_000,
            'tax_percent' => 11,
            'tax_amount' => 275_000,
            'total' => 2_775_000,
            'amount_paid' => 0,
            'balance_due' => 2_775_000,
            'status' => InvoiceStatus::Draft,
            'notes' => 'Menunggu MoU ditandatangani sebelum dikirim.',
            'billing_snapshot' => $client->billingSnapshot(),
            'assigned_to' => $this->person('admin'),
            'created_by' => $this->person('admin'),
        ]);

        $this->singleItem($draft, $package, 'Paket Lengkap Legalitas', 2_500_000);
    }

    /**
     * Klien lama yang sedang tidak ada proyek, dengan MoU yang segera berakhir.
     */
    private function seedQuietClient(): void
    {
        $today = Carbon::today();

        $client = $this->client('UD Berkah Mandiri', [
            'short_code' => 'BM',
            'email' => 'berkahmandiri@mail.test',
            'phone' => '0511-4466-881',
            'address' => 'Jl. Veteran No. 88',
            'city' => 'Banjarmasin',
            'contact_name' => 'Pak Sulaiman',
            'contact_position' => 'Pemilik',
            'status' => ClientStatus::Inactive,
            'notes' => 'Sempat pakai jasa legalitas, sekarang belum ada proyek berjalan.',
            'assigned_to' => $this->person('member'),
            'created_by' => $this->person('manager'),
        ]);

        $package = $this->package('Pembukuan Bulanan UMKM', 'Paket Dasar');

        if ($package === null) {
            return;
        }

        $contract = Contract::query()->updateOrCreate(
            ['number' => 'IM-MOU-0209-BM-001'],
            [
                'client_id' => $client->id,
                'type' => ContractType::Contract,
                'title' => 'Pembukuan Bulanan Enam Bulan',
                'start_date' => $today->copy()->subMonths(6),
                'end_date' => $today->copy()->addDays(18),
                'signing_place' => 'Banjarmasin',
                'signed_date' => $today->copy()->subMonths(6),
                'subtotal' => 3_000_000,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'value' => 3_000_000,
                'billing_cycle' => BillingCycle::OneTime,
                'first_party_name' => 'Pak Sulaiman',
                'first_party_position' => 'Pemilik',
                'status' => ContractStatus::Active,
                'created_by' => $this->person('manager'),
            ],
        );

        $contract->assignees()->sync([$this->person('member')]);

        $contract->items()->updateOrCreate(
            ['name' => 'Pembukuan Bulanan UMKM - Paket Dasar'],
            [
                'service_package_id' => $package->id,
                'description' => 'Pencatatan transaksi dan laporan laba rugi bulanan.',
                'quantity' => 6,
                'unit' => 'bulan',
                'unit_price' => 500_000,
                'amount' => 3_000_000,
                'position' => 0,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function client(string $name, array $attributes): Client
    {
        $assignee = $this->pickAssignee($attributes);

        $client = Client::query()->updateOrCreate(['company_name' => $name], $attributes);

        $client->assignees()->sync($assignee === null ? [] : [$assignee]);

        return $client;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function invoice(string $number, array $attributes): Invoice
    {
        $assignee = $this->pickAssignee($attributes);

        $invoice = Invoice::query()->updateOrCreate(['number' => $number], $attributes);

        $invoice->assignees()->sync($assignee === null ? [] : [$assignee]);

        return $invoice;
    }

    private function pickAssignee(array &$attributes): ?int
    {
        $id = $attributes['assigned_to'] ?? null;

        unset($attributes['assigned_to']);

        return $id === null ? null : (int) $id;
    }

    private function singleItem(Invoice $invoice, ?ServicePackage $package, string $label, float $price): void
    {
        $invoice->items()->updateOrCreate(
            ['name' => $label],
            [
                'service_package_id' => $package?->id,
                'quantity' => 1,
                'unit' => 'paket',
                'unit_price' => $price,
                'amount' => $price,
                'position' => 0,
            ],
        );
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
