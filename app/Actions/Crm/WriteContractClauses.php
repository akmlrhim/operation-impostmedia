<?php

namespace App\Actions\Crm;

use App\Enums\ContractType;
use App\Exceptions\AiUnavailable;
use App\Models\CompanySetting;
use App\Models\Contract;
use App\Support\Ai\Groq;
use App\Support\Documents\BlockSchema;
use App\Support\Documents\DocumentVariables;
use App\Support\Documents\MouTemplate;

class WriteContractClauses
{
    private const SYSTEM = <<<'TEXT'
        Kamu penyusun dokumen perjanjian kerja sama (MoU) di sebuah agensi
        digital di Indonesia. Tugasmu menuliskan isi pasal-pasal yang diminta,
        khusus untuk satu MoU dengan layanan tertentu.

        Aturan:
        - Jawab HANYA dengan JSON: {"clauses": {"<id_pasal>": ["poin", "poin"]}, "requires_visit": true/false}
        - Pakai id pasal persis seperti yang diberikan, jangan diubah atau ditambah.
        - Bahasa Indonesia hukum yang lazim dipakai perjanjian, lugas, tanpa basa-basi.
        - Sebut para pihak sebagai PIHAK PERTAMA dan PIHAK KEDUA, bukan nama perusahaan.
        - PIHAK PERTAMA adalah klien yang membeli jasa. PIHAK KEDUA adalah agensi
          yang mengerjakannya. Jangan tertukar.
        - Satu poin = satu kalimat utuh berisi satu kewajiban, hak, atau ketentuan.
        - Poin harus menyesuaikan layanan yang benar-benar ada di MoU ini.
          Jangan menyinggung layanan yang tidak dijual di sini.
        - Jangan menyebut nominal harga atau nomor dokumen; itu sudah ada di pasal lain.
        - Jangan menulis nomor, bullet, atau kata "Pasal" di awal poin.
        - "requires_visit": true kalau ada layanan yang mengharuskan PIHAK KEDUA
          datang langsung ke lokasi PIHAK PERTAMA (foto/video di tempat, survei,
          kunjungan rutin, instalasi, dan sejenisnya). false kalau seluruh
          pekerjaan bisa dikerjakan dari jarak jauh.
        TEXT;

    private const MAX_POINTS = 10;

    public function __construct(private readonly Groq $groq) {}

    /**
     * @return array<string, array<int, string>> isi pasal per id blok
     *
     * @throws AiUnavailable
     */
    public function handle(Contract $contract): array
    {
        $blocks = self::clauseBlocks($contract);

        if ($blocks === []) {
            return [];
        }

        $answer = $this->groq->json(self::SYSTEM, $this->prompt($contract, $blocks), temperature: 0.3);
        $clauses = $this->clauses($answer, $blocks);

        $contract->update([
            'ai_clauses' => $clauses,
            'ai_requires_visit' => is_bool($answer['requires_visit'] ?? null)
                ? $answer['requires_visit']
                : null,
        ]);

        return $clauses;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function clauseBlocks(Contract $contract): array
    {
        return $contract->type === ContractType::Mou ? BlockSchema::aiClauses(MouTemplate::blocks()) : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $blocks
     */
    private function prompt(Contract $contract, array $blocks): string
    {
        $company = CompanySetting::current();

        $lines = [
            'PIHAK PERTAMA — klien yang membeli jasa: '.$contract->client?->company_name,
            'PIHAK KEDUA — agensi yang mengerjakan: '.$company->name,
            'Judul pekerjaan: '.$contract->title,
            'Jangka waktu: '.DocumentVariables::forContract($contract)['dokumen.durasi'],
            '',
            'Layanan yang dijual di MoU ini:',
        ];

        foreach ($contract->items as $item) {
            $visitHint = $item->servicePackage?->requires_visit
                ? ' — sudah ditandai admin sebagai layanan yang butuh kunjungan lokasi'
                : '';

            $lines[] = '- '.$item->name.' ('.(int) $item->quantity.' '.$item->unit.')'.$visitHint;

            foreach (preg_split('/\r\n|\r|\n/', (string) $item->description) ?: [] as $point) {
                if (trim($point) !== '') {
                    $lines[] = '  · '.trim($point);
                }
            }
        }

        if ($contract->scope) {
            $lines[] = '';
            $lines[] = 'Catatan ruang lingkup: '.$contract->scope;
        }

        $lines[] = '';
        $lines[] = 'Pasal yang harus ditulis:';

        foreach ($blocks as $id => $block) {
            $count = max(1, min(self::MAX_POINTS, (int) $block['count']));

            $lines[] = "- id \"{$id}\": {$block['topic']} — tulis {$count} poin";

            if (trim((string) $block['instruction']) !== '') {
                $lines[] = '  arahan tambahan: '.$block['instruction'];
            }
        }

        $lines[] = '';
        $lines[] = 'Tentukan juga "requires_visit" berdasarkan seluruh layanan di atas, '
            .'bukan cuma yang sudah ditandai admin.';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $answer
     * @param  array<string, array<string, mixed>>  $blocks
     * @return array<string, array<int, string>>
     *
     * @throws AiUnavailable
     */
    private function clauses(array $answer, array $blocks): array
    {
        $written = $answer['clauses'] ?? null;

        if (! is_array($written)) {
            throw AiUnavailable::emptyAnswer();
        }

        $clauses = [];

        foreach ($blocks as $id => $block) {
            $points = $this->points($written[$id] ?? null, (int) $block['count']);

            if ($points !== []) {
                $clauses[$id] = $points;
            }
        }

        if ($clauses === []) {
            throw AiUnavailable::emptyAnswer();
        }

        return $clauses;
    }

    /**
     * @return array<int, string>
     */
    private function points(mixed $raw, int $count): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $points = [];

        foreach ($raw as $point) {
            if (! is_string($point)) {
                continue;
            }

            $clean = trim(preg_replace('/^\s*(?:[-*\x{2022}]|\d+[.)]|[a-z][.)])\s*/ui', '', $point) ?? '');

            if ($clean !== '') {
                $points[] = mb_substr($clean, 0, 1000);
            }
        }

        return array_slice(
            array_values(array_unique($points)),
            0,
            max(1, min(self::MAX_POINTS, $count)),
        );
    }
}
