<?php

namespace Tests\Feature\Crm;

use App\Enums\ContractType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Support\Documents\BlockRenderer;
use App\Support\Documents\BlockSchema;
use App\Support\Documents\DocumentVariables;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BlockRenderer adalah mesin render dokumen blok yang masih dipakai untuk
 * mencetak MoU (lihat App\Support\Documents\MouTemplate), meski manajemen
 * template sudah dihapus. Tes ini memanggil BlockRenderer langsung, tanpa
 * lewat model/tabel template yang sudah tidak ada.
 */
class BlockRendererTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
    }

    public function test_rich_text_is_stripped_to_the_allowed_tags(): void
    {
        $html = app(BlockRenderer::class)->render(
            BlockSchema::normalize([
                [
                    'type' => BlockSchema::PARAGRAPH,
                    'html' => '<p onclick="x()"><strong>Tebal</strong><script>alert(1)</script> biasa</p>',
                ],
            ]),
            BlockSchema::settingDefaults(),
            DocumentVariables::sample(),
        );

        $this->assertStringContainsString('<strong>Tebal</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_signature_columns_line_up_even_when_one_caption_wraps(): void
    {
        $html = app(BlockRenderer::class)->render(
            BlockSchema::normalize([[
                'type' => BlockSchema::SIGNATURE,
                'columns' => [
                    ['title' => 'CLIENT', 'rows' => [], 'caption' => 'Direktur', 'name' => 'Rani', 'stamp' => false, 'signature' => false],
                    ['title' => 'THE COMPANY', 'rows' => [], 'caption' => str_repeat('Jabatan panjang sekali ', 6), 'name' => 'Budi', 'stamp' => false, 'signature' => false],
                ],
                'gap' => 32,
            ]]),
            BlockSchema::settingDefaults(),
            DocumentVariables::sample(),
        );

        $rows = explode('</tr>', $html);
        $withNames = array_values(array_filter($rows, fn (string $row): bool => str_contains($row, 'RANI')));

        $this->assertCount(1, $withNames);
        $this->assertStringContainsString('BUDI', $withNames[0]);

        $this->assertStringNotContainsString(
            'Jabatan panjang',
            $withNames[0],
            'Caption harus berdiri di baris sendiri, kalau tidak yang membungkus akan mendorong turun nama di kolom sebelahnya.',
        );
    }

    /**
     * Coretan yang menimpa e-meterai menutupi kode QR-nya, dan MoU yang sudah
     * dicetak begitu tidak bisa diverifikasi lagi.
     */
    public function test_the_signature_stands_clear_of_the_e_stamp(): void
    {
        $stamp = 'data:image/png;base64,STAMP';
        $signature = 'data:image/png;base64,SIGNATURE';

        $html = app(BlockRenderer::class)->render(
            BlockSchema::normalize([[
                'type' => BlockSchema::SIGNATURE,
                'columns' => [
                    ['title' => 'THE COMPANY', 'rows' => [], 'caption' => 'CEO', 'name' => 'Fadel', 'stamp' => true, 'signature' => true],
                ],
            ]]),
            BlockSchema::settingDefaults(),
            DocumentVariables::sample(),
            ['stamp' => $stamp, 'signature' => $signature],
        );

        $this->assertStringContainsString($stamp, $html);
        $this->assertStringContainsString($signature, $html);

        $marks = $this->markRow($html);

        $this->assertStringNotContainsString(
            'position: absolute',
            $marks,
            'Meterai dan tanda tangan kembali ditumpuk, jadi coretannya menutupi kode QR e-meterai.',
        );

        $this->assertStringContainsString('text-align: center', $marks);
        $this->assertMatchesRegularExpression(
            '/'.preg_quote($signature, '/').'"[^>]*margin-left: \d+px/',
            $marks,
            'Tanda tangan harus punya jarak kiri supaya tidak menempel ke meterai.',
        );
    }

    private function markRow(string $html): string
    {
        $rows = explode('</td>', $html);
        $marks = array_values(array_filter($rows, fn (string $row): bool => str_contains($row, 'SIGNATURE')));

        $this->assertCount(1, $marks);

        return $marks[0];
    }

    public function test_items_table_totals_follow_the_contract(): void
    {
        $contract = $this->makeContract();

        $html = app(BlockRenderer::class)->render(
            BlockSchema::normalize([['type' => BlockSchema::ITEMS_TABLE]]),
            BlockSchema::settingDefaults(),
            DocumentVariables::forContract($contract),
            contract: $contract,
        );

        $this->assertStringContainsString('Uraian Pekerjaan', $html);
        $this->assertStringContainsString('Jasa Desain', $html);
        $this->assertStringContainsString('Rp2.500.000', $html);
    }

    public function test_an_ampersand_in_an_uppercased_heading_stays_an_ampersand(): void
    {
        $html = app(BlockRenderer::class)->render(
            BlockSchema::normalize([
                ['type' => BlockSchema::HEADING, 'text' => 'GARANSI & MAINTENANCE', 'uppercase' => true],
            ]),
            BlockSchema::settingDefaults(),
            DocumentVariables::sample(),
        );

        $this->assertStringContainsString('GARANSI &amp; MAINTENANCE', $html);
        $this->assertStringNotContainsString('&AMP;', $html);
    }

    public function test_previewing_shows_a_marker_instead_of_the_fallback(): void
    {
        $html = app(BlockRenderer::class)->render(
            BlockSchema::normalize([[
                'type' => BlockSchema::AI_CLAUSE,
                'topic' => 'Hak dan Kewajiban Para Pihak',
                'count' => 5,
                'fallback' => 'Teks cadangan pasal.',
            ]]),
            BlockSchema::settingDefaults(),
            DocumentVariables::sample(),
            preview: true,
        );

        $this->assertStringContainsString('ditulis AI mengikuti layanan', $html);
        $this->assertStringNotContainsString('Teks cadangan pasal.', $html);
    }

    public function test_unknown_block_types_are_dropped_by_normalize(): void
    {
        $blocks = BlockSchema::normalize([
            ['type' => BlockSchema::HEADING, 'text' => 'Tetap ada'],
            ['type' => 'script_injection', 'text' => 'Harus dibuang'],
        ]);

        $this->assertCount(1, $blocks);
        $this->assertSame('Tetap ada', $blocks[0]['text']);
    }

    public function test_unknown_block_properties_are_dropped_by_normalize(): void
    {
        $blocks = BlockSchema::normalize([
            ['type' => BlockSchema::HEADING, 'text' => 'Tetap ada', 'onclick' => 'alert(1)'],
        ]);

        $this->assertSame('Tetap ada', $blocks[0]['text']);
        $this->assertArrayNotHasKey('onclick', $blocks[0]);
    }

    private function makeContract(): Contract
    {
        $client = Client::create([
            'company_name' => 'PT Kopi Nusantara',
            'address' => 'Jl. Melati 1, Banjarmasin',
            'contact_name' => 'Rani',
            'contact_position' => 'Direktur',
        ]);

        $contract = Contract::create([
            'number' => 'IM-MOU-0101-PRJ-'.fake()->unique()->numerify('###'),
            'client_id' => $client->id,
            'type' => ContractType::Mou,
            'title' => 'Pembuatan Website',
            'signed_date' => '2026-06-06',
            'start_date' => '2026-06-06',
            'end_date' => '2026-09-06',
        ]);

        ContractItem::create([
            'contract_id' => $contract->id,
            'name' => 'Jasa Desain',
            'quantity' => 1,
            'unit' => 'paket',
            'unit_price' => 2_500_000,
            'amount' => 2_500_000,
        ]);

        $contract->recalculate();

        return $contract->refresh()->load('items');
    }
}
