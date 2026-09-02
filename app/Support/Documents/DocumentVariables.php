<?php

namespace App\Support\Documents;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Contract;
use Carbon\CarbonInterface;

class DocumentVariables
{
    /**
     * @return array<int, array{group: string, items: array<int, array{key: string, label: string}>}>
     */
    public static function catalog(): array
    {
        return [
            ['group' => 'Dokumen', 'items' => [
                ['key' => 'dokumen.nomor', 'label' => 'Nomor dokumen'],
                ['key' => 'dokumen.judul', 'label' => 'Judul pekerjaan'],
                ['key' => 'dokumen.hari', 'label' => 'Nama hari tanda tangan'],
                ['key' => 'dokumen.tanggal', 'label' => 'Tanggal tanda tangan (6 Juni 2026)'],
                ['key' => 'dokumen.tanggal_panjang', 'label' => 'Hari + tanggal (Sabtu, 6 Juni 2026)'],
                ['key' => 'dokumen.tempat', 'label' => 'Tempat penandatanganan'],
                ['key' => 'dokumen.mulai', 'label' => 'Tanggal mulai'],
                ['key' => 'dokumen.selesai', 'label' => 'Tanggal berakhir'],
                ['key' => 'dokumen.durasi', 'label' => 'Lama kontrak (mis. 3 (tiga) bulan)'],
                ['key' => 'dokumen.lingkup', 'label' => 'Ruang lingkup'],
                ['key' => 'dokumen.pembayaran', 'label' => 'Ketentuan pembayaran'],
            ]],
            ['group' => 'Klien', 'items' => [
                ['key' => 'klien.perusahaan', 'label' => 'Nama perusahaan klien'],
                ['key' => 'klien.alamat', 'label' => 'Alamat klien'],
                ['key' => 'klien.kota', 'label' => 'Kota klien'],
                ['key' => 'klien.telepon', 'label' => 'No. telepon klien'],
                ['key' => 'klien.email', 'label' => 'Email klien'],
                ['key' => 'klien.kontak', 'label' => 'Nama PIC klien'],
                ['key' => 'klien.jabatan_kontak', 'label' => 'Jabatan PIC klien'],
            ]],
            ['group' => 'Perusahaan', 'items' => [
                ['key' => 'perusahaan.nama', 'label' => 'Nama perusahaan sendiri'],
                ['key' => 'perusahaan.alamat', 'label' => 'Alamat perusahaan'],
                ['key' => 'perusahaan.kota', 'label' => 'Kota perusahaan'],
                ['key' => 'perusahaan.telepon', 'label' => 'No. telepon perusahaan'],
                ['key' => 'perusahaan.email', 'label' => 'Email perusahaan'],
                ['key' => 'perusahaan.penandatangan', 'label' => 'Nama penandatangan'],
                ['key' => 'perusahaan.jabatan_penandatangan', 'label' => 'Jabatan penandatangan'],
            ]],
            ['group' => 'Penanda tangan', 'items' => [
                ['key' => 'pihak1.nama', 'label' => 'Nama pihak pertama'],
                ['key' => 'pihak1.jabatan', 'label' => 'Jabatan pihak pertama'],
                ['key' => 'pihak2.nama', 'label' => 'Nama pihak kedua'],
                ['key' => 'pihak2.jabatan', 'label' => 'Jabatan pihak kedua'],
            ]],
            ['group' => 'Nilai', 'items' => [
                ['key' => 'nilai.subtotal', 'label' => 'Subtotal'],
                ['key' => 'nilai.ppn_persen', 'label' => 'Persentase PPN'],
                ['key' => 'nilai.ppn', 'label' => 'Nominal PPN'],
                ['key' => 'nilai.total', 'label' => 'Nilai pekerjaan'],
                ['key' => 'nilai.terbilang', 'label' => 'Nilai dalam huruf'],
            ]],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function forContract(Contract $contract): array
    {
        $client = $contract->client;
        $company = CompanySetting::current();

        return array_merge(
            self::documentValues($contract),
            self::clientValues($client),
            self::companyValues($company),
            self::partyValues($contract, $client, $company),
            self::moneyValues($contract),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function sample(): array
    {
        $values = [];

        foreach (self::catalog() as $group) {
            foreach ($group['items'] as $item) {
                $values[$item['key']] = '['.$item['label'].']';
            }
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    private static function documentValues(Contract $contract): array
    {
        $signed = $contract->signed_date;

        return [
            'dokumen.nomor' => (string) $contract->number,
            'dokumen.judul' => (string) $contract->title,
            'dokumen.hari' => $signed?->translatedFormat('l') ?? '-',
            'dokumen.tanggal' => self::date($signed),
            'dokumen.tanggal_panjang' => $signed?->translatedFormat('l, j F Y') ?? '-',
            'dokumen.tempat' => (string) ($contract->signing_place ?: ''),
            'dokumen.mulai' => self::date($contract->start_date),
            'dokumen.selesai' => self::date($contract->end_date),
            'dokumen.durasi' => self::duration($contract->start_date, $contract->end_date),
            'dokumen.lingkup' => (string) ($contract->scope ?? ''),
            'dokumen.pembayaran' => (string) ($contract->payment_terms ?? ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function clientValues(Client $client): array
    {
        return [
            'klien.perusahaan' => (string) $client->company_name,
            'klien.alamat' => (string) ($client->address ?? ''),
            'klien.kota' => (string) ($client->city ?? ''),
            'klien.telepon' => (string) ($client->phone ?: $client->contact_phone ?: ''),
            'klien.email' => (string) ($client->email ?: $client->contact_email ?: ''),
            'klien.kontak' => (string) ($client->contact_name ?? ''),
            'klien.jabatan_kontak' => (string) ($client->contact_position ?? ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function companyValues(CompanySetting $company): array
    {
        return [
            'perusahaan.nama' => (string) $company->name,
            'perusahaan.alamat' => (string) ($company->address ?? ''),
            'perusahaan.kota' => (string) ($company->city ?? ''),
            'perusahaan.telepon' => (string) ($company->phone ?? ''),
            'perusahaan.email' => (string) ($company->email ?? ''),
            'perusahaan.penandatangan' => (string) ($company->signatory_name ?? ''),
            'perusahaan.jabatan_penandatangan' => (string) ($company->signatory_position ?? ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function partyValues(Contract $contract, Client $client, CompanySetting $company): array
    {
        return [
            'pihak1.nama' => (string) ($contract->first_party_name ?: $client->contact_name ?: ''),
            'pihak1.jabatan' => (string) ($contract->first_party_position ?: $client->contact_position ?: ''),
            'pihak2.nama' => (string) ($contract->second_party_name ?: $company->signatory_name ?: ''),
            'pihak2.jabatan' => (string) ($contract->second_party_position ?: $company->signatory_position ?: ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function moneyValues(Contract $contract): array
    {
        return [
            'nilai.subtotal' => self::rupiah($contract->subtotal),
            'nilai.ppn_persen' => (string) (int) $contract->tax_percent.'%',
            'nilai.ppn' => self::rupiah($contract->tax_amount),
            'nilai.total' => self::rupiah($contract->value),
            'nilai.terbilang' => self::spellOut((float) $contract->value).' rupiah',
        ];
    }

    public static function rupiah(mixed $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }

    private static function date(?CarbonInterface $date): string
    {
        return $date?->translatedFormat('j F Y') ?? '-';
    }

    private static function duration(?CarbonInterface $start, ?CarbonInterface $end): string
    {
        if ($start === null || $end === null) {
            return '-';
        }

        $months = max(1, (int) round($start->diffInDays($end) / 30));

        return $months.' ('.self::spellOut($months).') bulan';
    }

    private static function spellOut(float $number): string
    {
        $number = (int) floor(abs($number));

        $units = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        return match (true) {
            $number < 12 => $units[$number],
            $number < 20 => self::spellOut($number - 10).' belas',
            $number < 100 => self::spellOut(intdiv($number, 10)).' puluh'.self::remainder($number % 10),
            $number < 200 => 'seratus'.self::remainder($number % 100),
            $number < 1000 => self::spellOut(intdiv($number, 100)).' ratus'.self::remainder($number % 100),
            $number < 2000 => 'seribu'.self::remainder($number % 1000),
            $number < 1_000_000 => self::spellOut(intdiv($number, 1000)).' ribu'.self::remainder($number % 1000),
            $number < 1_000_000_000 => self::spellOut(intdiv($number, 1_000_000)).' juta'.self::remainder($number % 1_000_000),
            default => self::spellOut(intdiv($number, 1_000_000_000)).' miliar'.self::remainder($number % 1_000_000_000),
        };
    }

    private static function remainder(int $number): string
    {
        return $number === 0 ? '' : ' '.self::spellOut($number);
    }
}
