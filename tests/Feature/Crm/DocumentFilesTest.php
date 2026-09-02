<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(CrmMasterDataSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_company_logo_and_signature_are_stored_and_shown_on_documents(): void
    {
        $this->put(route('company.update'), [
            ...$this->companyPayload(),
            'logo' => UploadedFile::fake()->image('logo.png'),
            'signature' => UploadedFile::fake()->image('ttd.png'),
        ])->assertRedirect();

        $company = CompanySetting::current()->fresh();

        $this->assertNotNull($company->logo_path);
        $this->assertNotNull($company->signature_path);
        Storage::disk('public')->assertExists($company->logo_path);
        Storage::disk('public')->assertExists($company->signature_path);

        $this->assertStringStartsWith('data:image/', (string) $company->logoData());
    }

    public function test_replacing_the_logo_removes_the_previous_file(): void
    {
        $this->put(route('company.update'), [
            ...$this->companyPayload(),
            'logo' => UploadedFile::fake()->image('lama.png'),
        ])->assertRedirect();

        $first = CompanySetting::current()->fresh()->logo_path;

        $this->put(route('company.update'), [
            ...$this->companyPayload(),
            'logo' => UploadedFile::fake()->image('baru.png'),
        ])->assertRedirect();

        $second = CompanySetting::current()->fresh()->logo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_logo_can_be_cleared_without_touching_the_signature(): void
    {
        $this->put(route('company.update'), [
            ...$this->companyPayload(),
            'logo' => UploadedFile::fake()->image('logo.png'),
            'signature' => UploadedFile::fake()->image('ttd.png'),
        ])->assertRedirect();

        $signature = CompanySetting::current()->fresh()->signature_path;

        $this->put(route('company.update'), [
            ...$this->companyPayload(),
            'remove_logo' => true,
        ])->assertRedirect();

        $company = CompanySetting::current()->fresh();

        $this->assertNull($company->logo_path);
        $this->assertSame($signature, $company->signature_path);
        Storage::disk('public')->assertExists($signature);
    }

    public function test_only_images_are_accepted_as_logo(): void
    {
        $sebelum = CompanySetting::current()->logo_path;

        $this->put(route('company.update'), [
            ...$this->companyPayload(),
            'logo' => UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('logo');

        $this->assertSame($sebelum, CompanySetting::current()->fresh()->logo_path);
    }

    public function test_finalizing_a_contract_archives_a_pdf(): void
    {
        $contract = $this->makeContract();

        $this->post(route('contracts.finalize', $contract))->assertRedirect();

        $contract->refresh();

        $this->assertNotNull($contract->file_path);
        Storage::disk('public')->assertExists($contract->file_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('public')->get($contract->file_path));
    }

    public function test_sending_an_invoice_archives_a_pdf(): void
    {
        $invoice = $this->makeInvoice();

        $this->post(route('invoices.send', $invoice))->assertRedirect();

        $invoice->refresh();

        $this->assertNotNull($invoice->file_path);
        Storage::disk('public')->assertExists($invoice->file_path);
    }

    public function test_pdf_is_generated_on_demand_when_it_was_never_archived(): void
    {
        $contract = $this->makeContract();

        $this->assertNull($contract->file_path);

        $this->get(route('contracts.pdf', $contract))->assertOk();

        $this->assertNotNull($contract->fresh()->file_path);
    }

    public function test_invoice_pdf_downloads_with_a_filename_free_of_slashes(): void
    {
        $invoice = $this->makeInvoice();

        $response = $this->get(route('invoices.pdf', $invoice))->assertOk();

        $disposition = $response->headers->get('content-disposition');

        $this->assertStringContainsString('IM-KPN-001-05-26.pdf', (string) $disposition);
        $this->assertStringNotContainsString('IM-KPN/001', (string) $disposition);
    }

    public function test_attachment_download_falls_back_to_a_safe_name(): void
    {
        $contract = $this->makeContract();

        $this->post(route('attachments.store', ['type' => 'contracts', 'id' => $contract->id]), [
            'name' => 'scan/mou/final.pdf',
            'file' => UploadedFile::fake()->create('scan.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $attachment = $contract->attachments()->firstOrFail();

        $response = $this->get(route('attachments.download', $attachment))->assertOk();

        $this->assertStringContainsString(
            'scan-mou-final.pdf',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_attachment_can_be_uploaded_listed_and_removed(): void
    {
        $contract = $this->makeContract();

        $this->post(route('attachments.store', ['type' => 'contracts', 'id' => $contract->id]), [
            'file' => UploadedFile::fake()->create('scan-mou.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $attachment = $contract->attachments()->firstOrFail();

        $this->assertSame('scan-mou.pdf', $attachment->name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertGreaterThan(0, $attachment->size);
        Storage::disk('public')->assertExists($attachment->path);

        $this->get(route('attachments.download', $attachment))->assertOk();

        $this->delete(route('attachments.destroy', $attachment))->assertRedirect();

        Storage::disk('public')->assertMissing($attachment->path);
        $this->assertSame(0, $contract->attachments()->count());
    }

    public function test_attachment_rejects_unsupported_types_and_unknown_owners(): void
    {
        $contract = $this->makeContract();

        $this->post(route('attachments.store', ['type' => 'contracts', 'id' => $contract->id]), [
            'file' => UploadedFile::fake()->create('script.exe', 10),
        ])->assertSessionHasErrors('file');

        $this->post(route('attachments.store', ['type' => 'entah', 'id' => 1]), [
            'file' => UploadedFile::fake()->create('catatan.pdf', 10, 'application/pdf'),
        ])->assertNotFound();

        $this->assertSame(0, $contract->attachments()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function companyPayload(): array
    {
        return [
            'name' => 'PT Inovasi Media',
        ];
    }

    private function makeContract(): Contract
    {
        $client = Client::create([
            'short_code' => Client::generateShortCode('PT Kopi Nusantara'),
            'company_name' => 'PT Kopi Nusantara',
            'contact_name' => 'Dewi Lestari',
        ]);

        $package = $this->makeServicePackage();

        $contract = Contract::create([
            'number' => 'IM-MOU-1605-PRJ-001',
            'client_id' => $client->id,
            'title' => 'Pengelolaan Social Media',
            'signed_date' => '2026-05-16',
            'status' => 'signed',
            'tax_percent' => 11,
        ]);

        $contract->items()->create([
            'service_package_id' => $package->id,
            'name' => $package->name,
            'quantity' => 1,
            'unit' => 'bulan',
            'unit_price' => 8_000_000,
            'amount' => 8_000_000,
        ]);

        $contract->recalculate();

        return $contract->fresh();
    }

    private function makeInvoice(): Invoice
    {
        $contract = $this->makeContract();

        $invoice = Invoice::create([
            'number' => 'IM-KPN/001/05/26',
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
            'type' => 'invoice',
            'issue_date' => '2026-05-16',
            'due_date' => '2026-05-30',
            'tax_percent' => 11,
            'status' => 'draft',
            'billing_snapshot' => $contract->client->billingSnapshot(),
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
}
