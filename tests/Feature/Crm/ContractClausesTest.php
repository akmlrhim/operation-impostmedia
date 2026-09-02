<?php

namespace Tests\Feature\Crm;

use App\Actions\Crm\RenderContractDocument;
use App\Enums\ContractType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\User;
use App\Support\Documents\BlockSchema;
use App\Support\Documents\MouTemplate;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ContractClausesTest extends TestCase
{
    use RefreshDatabase;

    private const CLAUSE_ID = 'pasal-kewajiban-pihak-pertama';

    private const CLAUSE_TOPIC = 'Kewajiban PIHAK PERTAMA (klien)';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
        $this->actingAs(User::factory()->create());

        config(['services.groq.key' => 'kunci-tes', 'services.groq.model' => 'model-tes']);
    }

    public function test_it_writes_clauses_from_the_services_actually_sold_in_the_mou(): void
    {
        $this->fakeGroq([
            'Menayangkan 8 konten feed setiap bulan.',
            'Menyerahkan aset brand paling lambat 7 hari sebelum tayang.',
        ]);

        $contract = $this->makeContract();

        $this->post(route('contracts.clauses', $contract))->assertRedirect();

        $this->assertSame([
            self::CLAUSE_ID => [
                'Menayangkan 8 konten feed setiap bulan.',
                'Menyerahkan aset brand paling lambat 7 hari sebelum tayang.',
            ],
        ], $contract->refresh()->ai_clauses);

        Http::assertSent(function (Request $request): bool {
            $prompt = $request['messages'][1]['content'];

            return str_contains($prompt, 'Social Media Management')
                && str_contains($prompt, '8 Konten Feed Design')
                && str_contains($prompt, self::CLAUSE_TOPIC)
                && str_contains($prompt, self::CLAUSE_ID);
        });
    }

    public function test_it_strips_numbering_and_honours_the_point_count_set_in_the_template(): void
    {
        $this->fakeGroq([
            '1. Poin pertama.',
            '- Poin kedua.',
            'a) Poin ketiga.',
            'Poin keempat.',
            'Poin kelima.',
            'Poin keenam yang harusnya dibuang karena template hanya minta 5.',
        ]);

        $contract = $this->makeContract();

        $this->post(route('contracts.clauses', $contract));

        $this->assertSame(
            ['Poin pertama.', 'Poin kedua.', 'Poin ketiga.', 'Poin keempat.', 'Poin kelima.'],
            $contract->refresh()->ai_clauses[self::CLAUSE_ID],
        );
    }

    public function test_groq_being_down_leaves_the_clauses_untouched(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $contract = $this->makeContract();
        $contract->update(['ai_clauses' => [self::CLAUSE_ID => ['Poin lama yang sudah dikoreksi.']]]);

        $this->post(route('contracts.clauses', $contract))->assertRedirect();

        $this->assertSame(
            ['Poin lama yang sudah dikoreksi.'],
            $contract->refresh()->ai_clauses[self::CLAUSE_ID],
        );
    }

    public function test_written_clauses_are_printed_as_the_body_of_the_pasal(): void
    {
        Http::fake();

        $contract = $this->makeContract();
        $contract->update(['ai_clauses' => [self::CLAUSE_ID => ['Poin satu.', 'Poin dua.']]]);

        $html = app(RenderContractDocument::class)->handle($contract, persist: false);

        $this->assertStringContainsString('Poin satu.', $html);
        $this->assertStringContainsString('Poin dua.', $html);
        $this->assertStringNotContainsString('Menyediakan logo, profil perusahaan', $html);

        Http::assertNothingSent();
    }

    public function test_a_clause_that_was_never_written_prints_its_fallback(): void
    {
        Http::fake();

        $html = app(RenderContractDocument::class)->handle($this->makeContract(), persist: false);

        $this->assertStringContainsString('Menyediakan logo, profil perusahaan', $html);
        Http::assertNothingSent();
    }

    public function test_model_written_text_cannot_carry_tags_into_the_printed_document(): void
    {
        Http::fake();

        $contract = $this->makeContract();
        $contract->update(['ai_clauses' => [
            self::CLAUSE_ID => ['<script>alert(1)</script> Poin biasa.'],
        ]]);

        $html = app(RenderContractDocument::class)->handle($contract, persist: false);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('Poin biasa.', $html);
    }

    public function test_the_shipped_template_assumes_nothing_about_which_service_was_sold(): void
    {
        $blocks = (string) json_encode(MouTemplate::blocks());

        foreach (['website', 'landing page', 'seo', 'hosting', 'yoast', 'ekspedisi', 'pengiriman', 'google'] as $word) {
            $this->assertStringNotContainsStringIgnoringCase(
                $word,
                $blocks,
                "Template MoU masih memuat \"{$word}\", jadi MoU layanan lain ikut kebagian.",
            );
        }

        $this->assertCount(4, BlockSchema::aiClauses(MouTemplate::blocks()));
    }

    public function test_finalizing_writes_the_clauses_that_were_never_filled_in(): void
    {
        $this->fakeGroq(['Poin dari AI.']);

        $contract = $this->makeContract();

        $this->post(route('contracts.finalize', $contract))->assertRedirect();

        $contract->refresh();

        $this->assertSame(['Poin dari AI.'], $contract->ai_clauses[self::CLAUSE_ID]);
        $this->assertStringContainsString('Poin dari AI.', (string) $contract->body);
    }

    public function test_finalizing_never_overwrites_clauses_a_person_has_corrected(): void
    {
        $this->fakeGroq(['Poin baru dari AI.']);

        $contract = $this->makeContract();
        $contract->update(['ai_clauses' => [self::CLAUSE_ID => ['Poin hasil koreksi manusia.']]]);

        $this->post(route('contracts.finalize', $contract));

        $this->assertStringContainsString('Poin hasil koreksi manusia.', (string) $contract->refresh()->body);
        Http::assertNothingSent();
    }

    public function test_a_failing_ai_still_leaves_a_complete_archived_document(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $contract = $this->makeContract();

        $this->post(route('contracts.finalize', $contract))->assertRedirect();

        $contract->refresh();

        $this->assertStringContainsString('Menyediakan logo, profil perusahaan', (string) $contract->body);
        $this->assertNotNull($contract->file_path);
    }

    public function test_the_detail_page_offers_every_ai_clause_of_the_template_for_review(): void
    {
        Http::fake();

        $contract = $this->makeContract();
        $contract->update(['ai_clauses' => [self::CLAUSE_ID => ['Poin satu.']]]);

        $this->get(route('contracts.show', $contract))->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('aiClauses', true)
                ->where('documentEdited', false)
                ->has('clauses', 4)
                ->where('clauses.0.id', 'pasal-ruang-lingkup-pihak-pertama')
                ->where(
                    'clauses.2.points',
                    fn (Collection $points): bool => $points->all() === ['Poin satu.'],
                ),
        );
    }

    public function test_a_hand_edited_document_no_longer_takes_its_pasal_from_the_ai_clauses(): void
    {
        Http::fake();

        $contract = $this->makeContract();
        $contract->update([
            'ai_clauses' => [self::CLAUSE_ID => ['Poin tulisan AI.']],
            'document_body' => '<p>Dokumen hasil suntingan sendiri.</p>',
        ]);

        $html = app(RenderContractDocument::class)->handle($contract, persist: false);

        $this->assertStringContainsString('Dokumen hasil suntingan sendiri.', $html);
        $this->assertStringNotContainsString('Poin tulisan AI.', $html);

        // Halaman detail harus mengakui itu, bukan diam-diam menawarkan tombol
        // yang tidak lagi mengubah apa pun di dokumen.
        $this->get(route('contracts.show', $contract))->assertInertia(
            fn (AssertableInertia $page) => $page->where('documentEdited', true),
        );
    }

    public function test_without_a_key_the_clauses_are_still_listed_but_only_editable_by_hand(): void
    {
        Http::fake();
        config(['services.groq.key' => null]);

        $contract = $this->makeContract();

        $this->get(route('contracts.show', $contract))->assertInertia(
            fn (AssertableInertia $page) => $page->where('aiClauses', false)->has('clauses', 4),
        );

        $this->post(route('contracts.clauses', $contract))->assertRedirect();

        $this->assertNull($contract->refresh()->ai_clauses);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'groq.com'));
    }

    /**
     * @param  array<int, string>  $points
     */
    private function fakeGroq(array $points): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['clauses' => [self::CLAUSE_ID => $points]]),
                    ],
                ]],
            ]),
        ]);
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
            'title' => 'Pengelolaan Media Sosial',
            'signed_date' => '2026-06-06',
            'start_date' => '2026-06-06',
            'end_date' => '2026-09-06',
        ]);

        ContractItem::create([
            'contract_id' => $contract->id,
            'name' => 'Social Media Management — Silver',
            'description' => "8 Konten Feed Design\n8 Video Reels",
            'quantity' => 3,
            'unit' => 'bulan',
            'unit_price' => 5_000_000,
            'amount' => 15_000_000,
        ]);

        $contract->recalculate();

        return $contract->refresh();
    }
}
