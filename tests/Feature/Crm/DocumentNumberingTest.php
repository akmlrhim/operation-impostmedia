<?php

namespace Tests\Feature\Crm;

use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentSequence;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_mou_number_follows_the_signing_date_and_keeps_counting(): void
    {
        $client = $this->makeClient('PT Kopi Nusantara');

        $this->storeContract($client, '2026-05-16');
        $this->storeContract($client, '2026-05-16');
        $this->storeContract($client, '2026-08-03');

        $numbers = Contract::query()->orderBy('id')->pluck('number')->all();

        $this->assertSame([
            'IM-MOU-1605-PRJ-001',
            'IM-MOU-1605-PRJ-002',
            'IM-MOU-0308-PRJ-003',
        ], $numbers);
    }

    public function test_mou_cannot_be_created_without_the_date_that_its_number_is_built_from(): void
    {
        $client = $this->makeClient('PT Kopi Nusantara');

        $this->post(route('contracts.store'), $this->contractPayload($client))
            ->assertSessionHasErrors('signed_date');

        $this->assertSame(0, Contract::count());
    }

    public function test_invoice_number_uses_the_client_short_code_and_its_own_running_number(): void
    {
        $wahana = $this->makeClient('PT Wahana Otomotif Sejahtera');
        $karya = $this->makeClient('CV Karya Bakti');
        $date = Carbon::create(2026, 5, 20);

        $numbers = [
            DocumentSequence::next(DocumentType::Invoice, date: $date, client: $wahana),
            DocumentSequence::next(DocumentType::Invoice, date: $date, client: $wahana),
            DocumentSequence::next(DocumentType::Invoice, date: $date, client: $karya),
            DocumentSequence::next(DocumentType::Invoice, date: $date, client: $wahana),
        ];

        $this->assertSame([
            'IM-WOS/001/05/26',
            'IM-WOS/002/05/26',
            'IM-KB/001/05/26',
            'IM-WOS/003/05/26',
        ], $numbers);
    }

    public function test_invoice_running_number_does_not_reset_between_months(): void
    {
        $client = $this->makeClient('PT Wahana Otomotif Sejahtera');

        DocumentSequence::next(DocumentType::Invoice, date: Carbon::create(2026, 5, 20), client: $client);

        $this->assertSame(
            'IM-WOS/002/12/26',
            DocumentSequence::next(DocumentType::Invoice, date: Carbon::create(2026, 12, 1), client: $client),
        );
    }

    public function test_preview_suggests_the_next_number_without_consuming_it(): void
    {
        $client = $this->makeClient('PT Wahana Otomotif Sejahtera');
        $date = Carbon::create(2026, 5, 20);

        $this->assertSame(
            'IM-WOS/001/05/26',
            DocumentSequence::preview(DocumentType::Invoice, date: $date, client: $client),
        );

        $this->assertSame(
            'IM-WOS/001/05/26',
            DocumentSequence::next(DocumentType::Invoice, date: $date, client: $client),
        );
    }

    public function test_next_number_endpoint_returns_a_suggestion_for_the_selected_client(): void
    {
        $client = $this->makeClient('PT Wahana Otomotif Sejahtera');

        $this->getJson(route('invoices.next-number', ['client' => $client->id]))
            ->assertOk()
            ->assertJsonPath('number', 'IM-WOS/001/'.now()->format('m/y'));
    }

    public function test_invoice_created_from_a_contract_is_numbered_for_that_client(): void
    {
        $client = $this->makeClient('PT Wahana Otomotif Sejahtera');
        $this->storeContract($client, '2026-05-16');

        $this->post(route('contracts.invoice', Contract::firstOrFail()))->assertRedirect();

        $this->assertSame(
            'IM-WOS/001/'.now()->format('m/y'),
            Invoice::firstOrFail()->number,
        );
    }

    public function test_saving_a_document_with_its_suggested_number_advances_the_counter(): void
    {
        $client = $this->makeClient('PT Kopi Nusantara');

        $this->storeInvoice($client, DocumentSequence::preview(DocumentType::Invoice, client: $client));

        $this->assertSame(
            'IM-KN/002/'.now()->format('m/y'),
            DocumentSequence::preview(DocumentType::Invoice, client: $client),
        );

        $signedDate = Carbon::create(2026, 5, 16);

        $this->storeContract(
            $client,
            $signedDate->toDateString(),
            DocumentSequence::preview(DocumentType::Contract, date: $signedDate),
        );

        $this->assertSame(
            'IM-MOU-1605-PRJ-002',
            DocumentSequence::preview(DocumentType::Contract, date: $signedDate),
        );
    }

    public function test_mou_number_from_another_date_is_treated_as_a_manual_number(): void
    {
        $client = $this->makeClient('PT Kopi Nusantara');

        $this->storeContract($client, '2026-05-16', DocumentSequence::preview(DocumentType::Contract));

        $this->assertSame(
            'IM-MOU-1605-PRJ-001',
            DocumentSequence::preview(DocumentType::Contract, date: Carbon::create(2026, 5, 16)),
        );
    }

    public function test_a_manually_typed_number_does_not_shift_the_running_order(): void
    {
        $client = $this->makeClient('PT Kopi Nusantara');

        $this->storeInvoice($client, 'INV-MANUAL/2026/99');

        $this->assertSame(
            'IM-KN/001/'.now()->format('m/y'),
            DocumentSequence::preview(DocumentType::Invoice, client: $client),
        );
    }

    public function test_document_number_is_generated_when_the_field_is_left_empty(): void
    {
        $client = $this->makeClient('PT Kopi Nusantara');

        $this->storeInvoice($client, null);

        $this->assertSame('IM-KN/001/'.now()->format('m/y'), Invoice::firstOrFail()->number);
    }

    public function test_both_create_pages_carry_a_filled_in_number_suggestion(): void
    {
        $client = $this->makeClient('PT Kopi Nusantara');

        $this->get(route('contracts.create'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('suggestedNumber', 'IM-MOU-'.now()->format('dm').'-PRJ-001')
            ->etc());

        $this->get(route('invoices.create', ['client' => $client->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('suggestedNumber', 'IM-KN/001/'.now()->format('m/y'))
                ->etc());
    }

    public function test_mou_number_suggestion_follows_the_date_chosen_in_the_form(): void
    {
        $this->getJson(route('contracts.next-number', ['date' => '2026-05-16']))
            ->assertOk()
            ->assertJsonPath('number', 'IM-MOU-1605-PRJ-001');
    }

    public function test_invoice_number_suggestion_follows_the_issue_date_chosen_in_the_form(): void
    {
        $client = $this->makeClient('PT Wahana Otomotif Sejahtera');

        $this->getJson(route('invoices.next-number', ['client' => $client->id, 'date' => '2026-12-01']))
            ->assertOk()
            ->assertJsonPath('number', 'IM-WOS/001/12/26');
    }

    public function test_new_client_gets_a_short_code_derived_from_its_company_name(): void
    {
        $this->post(route('clients.store'), [
            'company_name' => 'PT Wahana Otomotif Sejahtera',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertSame('WOS', Client::firstOrFail()->short_code);
    }

    public function test_short_code_can_be_set_manually_and_must_stay_unique(): void
    {
        $this->post(route('clients.store'), [
            'company_name' => 'PT Wahana Otomotif Sejahtera',
            'short_code' => 'wos',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertSame('WOS', Client::firstOrFail()->short_code);

        $this->post(route('clients.store'), [
            'company_name' => 'PT Warna Optik Sentosa',
            'short_code' => 'WOS',
            'status' => 'active',
        ])->assertSessionHasErrors('short_code');
    }

    public function test_short_code_endpoint_suggests_a_code_from_the_company_name(): void
    {
        $this->getJson(route('clients.short-code', ['company_name' => 'PT Wahana Otomotif Sejahtera']))
            ->assertOk()
            ->assertJsonPath('short_code', 'WOS');

        $this->getJson(route('clients.short-code', ['company_name' => '   ']))
            ->assertOk()
            ->assertJsonPath('short_code', '');
    }

    public function test_short_code_suggestion_avoids_codes_already_taken(): void
    {
        $existing = $this->makeClient('PT Wahana Otomotif Sejahtera');

        $this->getJson(route('clients.short-code', ['company_name' => 'PT Warna Optik Sentosa']))
            ->assertOk()
            ->assertJsonPath('short_code', 'WOS2');

        $this->getJson(route('clients.short-code', [
            'company_name' => 'PT Wahana Otomotif Sejahtera',
            'client' => $existing->id,
        ]))->assertOk()->assertJsonPath('short_code', 'WOS');
    }

    public function test_colliding_short_codes_are_given_a_running_suffix(): void
    {
        $first = $this->makeClient('PT Wahana Otomotif Sejahtera');
        $second = $this->makeClient('PT Warna Optik Sentosa');

        $this->assertSame('WOS', $first->short_code);
        $this->assertSame('WOS2', $second->short_code);
    }

    public function test_short_code_drops_the_legal_form_and_shortens_single_word_names(): void
    {
        $this->assertSame('BCA', Client::deriveShortCode('PT Bank Central Asia Tbk'));
        $this->assertSame('KB', Client::deriveShortCode('CV Karya Bakti'));
        $this->assertSame('TOK', Client::deriveShortCode('Tokopedia'));
        $this->assertSame('KLIEN', Client::deriveShortCode('PT'));
    }

    private function makeClient(string $companyName): Client
    {
        return Client::create([
            'short_code' => Client::generateShortCode($companyName),
            'company_name' => $companyName,
            'contact_name' => 'PIC '.$companyName,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function contractPayload(Client $client): array
    {
        $package = $this->makeServicePackage();

        return [
            'client_id' => $client->id,
            'type' => 'mou',
            'title' => 'Pengelolaan Social Media',
            'tax_percent' => 11,
            'billing_cycle' => 'monthly',
            'status' => 'signed',
            'items' => [
                [
                    'service_package_id' => $package->id,
                    'name' => $package->name,
                    'quantity' => 12,
                    'unit' => 'bulan',
                    'unit_price' => 8_000_000,
                ],
            ],
        ];
    }

    private function storeContract(Client $client, string $signedDate, ?string $number = null): void
    {
        $this->post(route('contracts.store'), [
            ...$this->contractPayload($client),
            'signed_date' => $signedDate,
            ...($number === null ? [] : ['number' => $number]),
        ])->assertRedirect();
    }

    private function storeInvoice(Client $client, ?string $number): void
    {
        $package = $this->makeServicePackage();

        $this->post(route('invoices.store'), [
            ...($number === null ? [] : ['number' => $number]),
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'discount_amount' => 0,
            'tax_percent' => 11,
            'status' => 'draft',
            'items' => [
                [
                    'service_package_id' => $package->id,
                    'name' => $package->name,
                    'quantity' => 1,
                    'unit' => 'bulan',
                    'unit_price' => 8_000_000,
                ],
            ],
        ])->assertRedirect();
    }
}
