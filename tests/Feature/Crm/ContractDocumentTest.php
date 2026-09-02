<?php

namespace Tests\Feature\Crm;

use App\Actions\Crm\RenderContractDocument;
use App\Enums\ContractType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\User;
use App\Support\Documents\HtmlSanitizer;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ContractDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_the_editor_opens_on_the_document_built_from_the_template(): void
    {
        $contract = $this->makeContract();

        $this->get(route('contracts.document', $contract))->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('edited', false)
                ->where('contract.number', $contract->number)
                ->where('page', fn (string $html): bool => str_contains($html, 'RUANG LINGKUP KERJASAMA')
                    && str_contains($html, 'class="doc-body"')
                    && str_contains($html, 'PT Kopi Nusantara')),
        );
    }

    public function test_package_points_are_printed_one_per_line_under_the_work_item(): void
    {
        $contract = $this->makeContract();
        $contract->items()->first()?->update([
            'description' => "12 Konten Feed Design\n8 Video Reels",
        ]);

        $html = app(RenderContractDocument::class)->handle($contract->refresh(), persist: false);

        $this->assertStringContainsString('12 Konten Feed Design<br', $html);
        $this->assertStringContainsString('8 Video Reels', $html);
    }

    public function test_saving_replaces_the_template_layout_for_this_mou_only(): void
    {
        $contract = $this->makeContract();
        $other = $this->makeContract();

        $this->put(route('contracts.document.update', $contract), [
            'body' => '<p>Kalimat yang diketik sendiri.</p>',
        ])->assertRedirect();

        $html = app(RenderContractDocument::class)->handle($contract->refresh(), persist: false);

        $this->assertStringContainsString('Kalimat yang diketik sendiri.', $html);
        $this->assertStringNotContainsString('RUANG LINGKUP KERJASAMA', $html);

        $this->assertStringContainsString('class="doc-body"', $html);
        $this->assertStringContainsString('<style>', $html);

        $this->assertStringContainsString(
            'RUANG LINGKUP KERJASAMA',
            app(RenderContractDocument::class)->handle($other, persist: false),
        );
    }

    public function test_saving_drops_the_frozen_body_so_printing_shows_the_edit(): void
    {
        Storage::fake('public');

        $contract = $this->makeContract();
        $this->post(route('contracts.finalize', $contract));

        $this->assertNotNull($contract->refresh()->body);

        $this->put(route('contracts.document.update', $contract), [
            'body' => '<p>Versi hasil suntingan.</p>',
        ]);

        $this->assertNull($contract->refresh()->body);

        $this->get(route('contracts.print', $contract))
            ->assertOk()
            ->assertSee('Versi hasil suntingan.', escape: false);
    }

    public function test_an_already_archived_pdf_is_rewritten_so_the_download_is_not_stale(): void
    {
        Storage::fake('public');

        $contract = $this->makeContract();
        $this->post(route('contracts.finalize', $contract));

        $path = $contract->refresh()->file_path;
        $this->assertNotNull($path);

        $before = Storage::disk('public')->get($path);

        $this->put(route('contracts.document.update', $contract), [
            'body' => '<p>Isi yang benar-benar berbeda supaya berkasnya ikut berubah.</p>',
        ]);

        $this->assertSame($path, $contract->refresh()->file_path);
        $this->assertNotSame($before, Storage::disk('public')->get($path));
    }

    public function test_a_contract_without_an_archive_yet_is_not_given_one_by_saving(): void
    {
        Storage::fake('public');

        $contract = $this->makeContract();

        $this->put(route('contracts.document.update', $contract), ['body' => '<p>Draf.</p>']);

        $this->assertNull($contract->refresh()->file_path);
    }

    public function test_reverting_puts_the_mou_back_under_its_template(): void
    {
        $contract = $this->makeContract();

        $this->put(route('contracts.document.update', $contract), ['body' => '<p>Suntingan.</p>']);
        $this->delete(route('contracts.document.reset', $contract))->assertRedirect();

        $contract->refresh();

        $this->assertNull($contract->document_body);
        $this->assertNull($contract->body);
        $this->assertStringContainsString(
            'RUANG LINGKUP KERJASAMA',
            app(RenderContractDocument::class)->handle($contract, persist: false),
        );
    }

    public function test_the_editor_reopens_on_what_was_saved(): void
    {
        $contract = $this->makeContract();

        $this->put(route('contracts.document.update', $contract), [
            'body' => '<p>Yang terakhir diketik.</p>',
        ]);

        $this->get(route('contracts.document', $contract))->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('edited', true)
                ->where('page', fn (string $html): bool => str_contains($html, 'Yang terakhir diketik.')),
        );
    }

    public function test_pasted_markup_cannot_smuggle_scripts_into_the_document(): void
    {
        $contract = $this->makeContract();

        $this->put(route('contracts.document.update', $contract), [
            'body' => '<p onclick="steal()">Isi sah.</p><script>alert(1)</script>',
        ]);

        $saved = (string) $contract->refresh()->document_body;

        $this->assertStringContainsString('Isi sah.', $saved);
        $this->assertStringNotContainsString('onclick', $saved);
        $this->assertStringNotContainsString('alert(1)', $saved);
    }

    public function test_the_sanitizer_keeps_the_shape_of_a_document_but_drops_the_rest(): void
    {
        $clean = HtmlSanitizer::clean(
            '<div style="text-align: center"><h2>PASAL 1</h2>'
            .'<table><tr><td colspan="2" style="width: 50%">Sel</td></tr></table>'
            .'<ul><li><strong>Butir</strong></li></ul>'
            .'<marquee>Kalimat di dalam tag asing</marquee>'
            .'<style>.x{color:red}</style></div>',
        );

        $this->assertStringContainsString('<h2>PASAL 1</h2>', $clean);
        $this->assertStringContainsString('colspan="2"', $clean);
        $this->assertStringContainsString('<strong>Butir</strong>', $clean);
        $this->assertStringContainsString('text-align: center', $clean);

        $this->assertStringNotContainsString('<marquee>', $clean);
        $this->assertStringContainsString('Kalimat di dalam tag asing', $clean);

        $this->assertStringNotContainsString('color:red', $clean);
    }

    public function test_styles_that_reach_out_to_other_servers_are_dropped(): void
    {
        $clean = HtmlSanitizer::clean(
            '<p style="color: #111; background: url(https://jauh.example/x.png)">Teks</p>',
        );

        $this->assertStringContainsString('color: #111', $clean);
        $this->assertStringNotContainsString('jauh.example', $clean);
    }

    public function test_images_from_other_servers_are_dropped_but_embedded_ones_stay(): void
    {
        $embedded = 'data:image/png;base64,iVBORw0KGgo=';

        $clean = HtmlSanitizer::clean(
            '<p><img src="https://jauh.example/pixel.png" alt="a"><img src="'.$embedded.'" alt="ttd"></p>',
        );

        $this->assertStringNotContainsString('jauh.example', $clean);
        $this->assertStringContainsString($embedded, $clean);
    }

    public function test_accented_letters_survive_the_sanitizer(): void
    {
        $this->assertStringContainsString(
            'Perjanjian iní — daftar “kutipan”',
            HtmlSanitizer::clean('<p>Perjanjian iní — daftar “kutipan”</p>'),
        );
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
            'name' => 'Social Media Management',
            'quantity' => 3,
            'unit' => 'bulan',
            'unit_price' => 5_000_000,
            'amount' => 15_000_000,
        ]);

        $contract->recalculate();

        return $contract->refresh();
    }
}
