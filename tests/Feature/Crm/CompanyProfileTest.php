<?php

namespace Tests\Feature\Crm;

use App\Actions\Crm\RenderContractDocument;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\User;
use App\Support\CompanyProfile;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_the_profile_comes_from_the_config_file(): void
    {
        $this->assertSame(config('company.name'), CompanyProfile::name());
        $this->assertSame(config('company.address'), CompanyProfile::address());
        $this->assertSame(config('company.city'), CompanyProfile::city());
        $this->assertSame(config('company.phone'), CompanyProfile::phone());
        $this->assertSame(config('company.email'), CompanyProfile::email());
        $this->assertSame(config('company.signatory_name'), CompanyProfile::signatoryName());
        $this->assertSame(config('company.signatory_position'), CompanyProfile::signatoryPosition());
    }

    public function test_the_settings_table_no_longer_holds_any_identity(): void
    {
        $columns = array_keys(CompanySetting::current()->getAttributes());

        foreach (CompanyProfile::IDENTITY as $field) {
            $this->assertNotContains($field, $columns);
        }
    }

    public function test_the_file_values_reach_the_rendered_mou(): void
    {
        $html = $this->renderMou();

        $rendered = [
            CompanyProfile::name(),
            CompanyProfile::address(),
            CompanyProfile::phone(),
            CompanyProfile::email(),
            CompanyProfile::signatoryName(),
            CompanyProfile::signatoryPosition(),
        ];

        foreach ($rendered as $value) {
            $this->assertStringContainsString($value, $html, "hilang: {$value}");
        }

        $this->assertStringContainsString((string) CompanyProfile::logoData(), $html);
    }

    public function test_editing_the_file_changes_the_mou_without_touching_the_database(): void
    {
        config([
            'company.name' => 'CV. Nama Baru',
            'company.signatory_name' => 'Budi Santoso',
            'company.signatory_position' => 'Direktur Utama',
        ]);

        $html = $this->renderMou();

        $this->assertStringContainsString('CV. Nama Baru', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Direktur Utama', $html);
        $this->assertStringNotContainsString('CV. Impost Media Indonesia', $html);
    }

    public function test_the_settings_page_shows_the_profile_and_the_bundled_logo(): void
    {
        $this->assertStringStartsWith('/pdf/logo.png?v=', (string) CompanyProfile::logoUrl());

        $this->get(route('company.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('profile.name', CompanyProfile::name())
                ->where('profile.signatory_name', CompanyProfile::signatoryName())
                ->where('logoUrl', CompanyProfile::logoUrl())
                ->missing('settings'));
    }

    public function test_invoice_notes_and_terms_are_printed_from_the_file(): void
    {
        $invoice = $this->makeInvoice();

        $pdf = view('documents.invoice', [
            'invoice' => $invoice,
            'company' => CompanySetting::current(),
            'profile' => CompanyProfile::all(),
        ])->render();

        $this->assertStringContainsString(CompanyProfile::invoiceNotes(), $pdf);
        $this->assertStringContainsString(CompanyProfile::terms(), $pdf);
        $this->assertStringContainsString('white-space: pre-line', $pdf);
    }

    private function makeInvoice(): Invoice
    {
        $client = Client::firstOrCreate(
            ['company_name' => 'PT Kopi Nusantara'],
            ['contact_name' => 'Dewi Lestari', 'contact_position' => 'Direktur'],
        );

        $invoice = Invoice::create([
            'number' => 'IM-KPN-001-09-26',
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-09-08',
            'due_date' => '2026-09-09',
            'tax_percent' => 0,
            'status' => 'draft',
            'billing_snapshot' => $client->billingSnapshot(),
        ]);

        $invoice->items()->create([
            'name' => 'Social Media Management',
            'quantity' => 1,
            'unit' => 'bulan',
            'unit_price' => 8_000_000,
            'amount' => 8_000_000,
        ]);

        $invoice->recalculate();

        return $invoice->fresh();
    }

    private function renderMou(): string
    {
        $client = Client::firstOrCreate(
            ['company_name' => 'PT Kopi Nusantara'],
            ['contact_name' => 'Dewi Lestari', 'contact_position' => 'Direktur'],
        );

        $contract = Contract::firstOrCreate(
            ['number' => 'IM-MOU-0908-PRJ-001'],
            [
                'client_id' => $client->id,
                'type' => 'mou',
                'title' => 'Pengelolaan Social Media',
                'signed_date' => '2026-09-08',
                'status' => 'signed',
                'tax_percent' => 11,
            ],
        );

        return app(RenderContractDocument::class)->handle($contract, persist: false);
    }
}
