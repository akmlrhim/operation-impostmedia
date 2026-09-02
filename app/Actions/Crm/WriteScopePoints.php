<?php

namespace App\Actions\Crm;

use App\Exceptions\AiUnavailable;
use App\Models\ServicePackage;
use App\Support\Ai\Groq;

class WriteScopePoints
{
    private const MAX_POINTS = 8;

    private const SYSTEM = <<<'TEXT'
        Kamu penyusun dokumen MoU di sebuah agensi digital di Indonesia.
        Tugasmu menulis rincian deliverable untuk satu baris pekerjaan.

        Aturan:
        - Jawab HANYA dengan JSON: {"points": ["...", "..."]}
        - Bahasa Indonesia, gaya dokumen resmi, tanpa sapaan atau basa-basi.
        - Satu poin = satu deliverable konkret yang bisa dicek selesai atau belum.
        - Maksimal 12 kata per poin. Tanpa tanda hubung, bullet, atau nomor di awal.
        - Sebutkan jumlah bila masuk akal, mis. "8 konten feed design".
        - Jangan menyebut harga, termin pembayaran, atau nama pihak.
        - Jangan mengarang layanan di luar pekerjaan yang diminta.
        TEXT;

    public function __construct(private readonly Groq $groq) {}

    /**
     * @param  string  $work  Uraian baris, mis. "Social Media Management — Paket Basic"
     * @param  ServicePackage|null  $package  Paket katalog asal baris, bila ada
     * @return list<string>
     *
     * @throws AiUnavailable
     */
    public function handle(
        string $work,
        ?ServicePackage $package = null,
        ?string $clientName = null,
        ?string $contractTitle = null,
    ): array {
        $answer = $this->groq->json(self::SYSTEM, $this->prompt($work, $package, $clientName, $contractTitle));

        return $this->points($answer);
    }

    private function prompt(
        string $work,
        ?ServicePackage $package,
        ?string $clientName,
        ?string $contractTitle,
    ): string {
        $lines = ["Pekerjaan: {$work}"];

        if ($package !== null) {
            $lines[] = "Layanan: {$package->service?->name} ({$package->service?->type->label()})";
            $lines[] = "Paket: {$package->name}, dihitung per {$package->unit}";

            if ($package->description) {
                $lines[] = "Catatan paket: {$package->description}";
            }

            $catalog = $package->points->pluck('label')->all();

            if ($catalog !== []) {
                $lines[] = 'Poin yang sudah ada di katalog (pertahankan maknanya, boleh dipertajam): '
                    .implode('; ', $catalog);
            }
        }

        if ($clientName !== null && $clientName !== '') {
            $lines[] = "Klien: {$clientName}";
        }

        if ($contractTitle !== null && $contractTitle !== '') {
            $lines[] = "Judul MoU: {$contractTitle}";
        }

        $lines[] = 'Tulis 3 sampai '.self::MAX_POINTS.' poin rincian untuk pekerjaan ini.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $answer
     * @return list<string>
     *
     * @throws AiUnavailable
     */
    private function points(array $answer): array
    {
        $raw = $answer['points'] ?? null;

        if (! is_array($raw)) {
            throw AiUnavailable::emptyAnswer();
        }

        $points = [];

        foreach ($raw as $point) {
            if (! is_string($point)) {
                continue;
            }

            $clean = trim(preg_replace('/^\s*(?:[-*\x{2022}]|\d+[.)])\s*/u', '', $point) ?? '');

            if ($clean !== '') {
                $points[] = mb_substr($clean, 0, 255);
            }
        }

        if ($points === []) {
            throw AiUnavailable::emptyAnswer();
        }

        return array_slice(array_values(array_unique($points)), 0, self::MAX_POINTS);
    }
}
