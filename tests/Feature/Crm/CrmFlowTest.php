<?php

namespace Tests\Feature\Crm;

use App\Actions\Crm\ArchiveDocumentPdf;
use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\ServiceType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CrmFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_lead_can_be_created_and_appears_on_the_kanban(): void
    {
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        $this->post(route('leads.store'), [
            'lead_stage_id' => $stage->id,
            'company_name' => 'PT Kopi Nusantara',
            'contact_name' => 'Dewi Lestari',
            'estimated_value' => 96_000_000,
            'priority' => 'high',
            'status' => 'open',
        ])->assertRedirect();

        $lead = Lead::firstOrFail();

        $this->assertSame('PT Kopi Nusantara', $lead->company_name);

        $this->get(route('leads.index'))->assertOk();
    }

    public function test_lead_source_only_accepts_the_options_offered_in_the_dropdown(): void
    {
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        $payload = [
            'lead_stage_id' => $stage->id,
            'company_name' => 'PT Kopi Nusantara',
            'contact_name' => 'Dewi Lestari',
            'estimated_value' => 96_000_000,
            'priority' => 'high',
            'status' => 'open',
        ];

        $this->post(route('leads.store'), [...$payload, 'source' => 'referral'])->assertRedirect();

        $this->assertSame(LeadSource::Referral, Lead::firstOrFail()->source);

        $this->post(route('leads.store'), [...$payload, 'source' => 'lewat teman'])
            ->assertSessionHasErrors('source');

        $this->post(route('leads.store'), [...$payload, 'source' => ''])->assertRedirect();

        $this->assertNull(Lead::latest('id')->firstOrFail()->source);
    }

    public function test_moving_a_lead_reorders_cards_within_the_target_stage(): void
    {
        $from = LeadStage::where('slug', 'prospek-baru')->firstOrFail();
        $to = LeadStage::where('slug', 'negosiasi')->firstOrFail();

        $existing = $this->makeLead($to, 'PT Lama', position: 0);
        $moving = $this->makeLead($from, 'PT Pindah', position: 0);

        $this->post(route('leads.move', $moving), [
            'lead_stage_id' => $to->id,
            'position' => 0,
        ])->assertRedirect();

        $this->assertSame($to->id, $moving->fresh()->lead_stage_id);
        $this->assertSame(0, $moving->fresh()->position);
        $this->assertSame(1, $existing->fresh()->position);
    }

    public function test_a_card_dragged_down_within_its_own_column_lands_on_the_slot_it_was_dropped_on(): void
    {
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        $a = $this->makeLead($stage, 'PT A', position: 0);
        $this->makeLead($stage, 'PT B', position: 1);
        $this->makeLead($stage, 'PT C', position: 2);
        $this->makeLead($stage, 'PT D', position: 3);

        $this->post(route('leads.move', $a), [
            'lead_stage_id' => $stage->id,
            'position' => 2,
        ])->assertRedirect();

        $this->assertSame(['PT B', 'PT C', 'PT A', 'PT D'], $this->boardOrder($stage));
    }

    public function test_a_card_dragged_up_within_its_own_column_lands_on_the_slot_it_was_dropped_on(): void
    {
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        $this->makeLead($stage, 'PT A', position: 0);
        $this->makeLead($stage, 'PT B', position: 1);
        $this->makeLead($stage, 'PT C', position: 2);
        $d = $this->makeLead($stage, 'PT D', position: 3);

        $this->post(route('leads.move', $d), [
            'lead_stage_id' => $stage->id,
            'position' => 1,
        ])->assertRedirect();

        $this->assertSame(['PT A', 'PT D', 'PT B', 'PT C'], $this->boardOrder($stage));
    }

    public function test_moving_a_card_away_closes_the_gap_in_the_column_it_left(): void
    {
        $from = LeadStage::where('slug', 'prospek-baru')->firstOrFail();
        $to = LeadStage::where('slug', 'negosiasi')->firstOrFail();

        $this->makeLead($from, 'PT A', position: 0);
        $b = $this->makeLead($from, 'PT B', position: 1);
        $this->makeLead($from, 'PT C', position: 2);

        $this->post(route('leads.move', $b), [
            'lead_stage_id' => $to->id,
            'position' => 0,
        ])->assertRedirect();

        $this->assertSame(['PT A', 'PT C'], $this->boardOrder($from));
        $this->assertSame([0, 1], Lead::where('lead_stage_id', $from->id)
            ->orderBy('position')
            ->pluck('position')
            ->all());
    }

    /**
     * @return list<string>
     */
    private function boardOrder(LeadStage $stage): array
    {
        return Lead::query()
            ->where('lead_stage_id', $stage->id)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('company_name')
            ->all();
    }

    public function test_converting_a_lead_creates_a_client_and_marks_the_lead_won(): void
    {
        $lead = $this->makeLead(LeadStage::where('slug', 'negosiasi')->firstOrFail(), 'PT Konversi');

        $this->post(route('leads.convert', $lead))->assertRedirect();

        $client = Client::firstOrFail();
        $lead->refresh();

        $this->assertSame('PT Konversi', $client->company_name);
        $this->assertSame($client->id, $lead->converted_client_id);
        $this->assertSame(LeadStatus::Won, $lead->status);
        $this->assertNotNull($lead->converted_at);
    }

    public function test_redirect_after_converting_a_lead_does_not_force_open_any_modal(): void
    {
        $lead = $this->makeLead(LeadStage::where('slug', 'negosiasi')->firstOrFail(), 'PT Tanpa Modal Paksa');

        $response = $this->post(route('leads.convert', $lead));
        $client = Client::firstOrFail();

        $response->assertRedirect(route('clients.show', $client));

        $target = parse_url($response->headers->get('Location'), PHP_URL_QUERY);
        $this->assertNull($target);

        $this->get(route('contracts.create', ['client' => $client->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('contracts/create')
                ->where('clientId', $client->id)
                ->has('suggestedNumber'));

        $this->get(route('invoices.create', ['client' => $client->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('invoices/create')
                ->where('clientId', $client->id)
                ->has('suggestedNumber'));
    }

    public function test_contract_totals_are_calculated_from_its_line_items(): void
    {
        $client = $this->makeClient();
        $package = $this->makeServicePackage();

        $this->post(route('contracts.store'), [
            'client_id' => $client->id,
            'type' => 'mou',
            'title' => 'Pengelolaan Social Media 12 Bulan',
            'tax_percent' => 11,
            'billing_cycle' => 'monthly',
            'signed_date' => '2026-05-16',
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
        ])->assertRedirect();

        $contract = Contract::firstOrFail();

        $this->assertSame('96000000.00', $contract->subtotal);
        $this->assertSame('10560000.00', $contract->tax_amount);
        $this->assertSame('106560000.00', $contract->value);
        $this->assertNotEmpty($contract->number);
    }

    public function test_retainer_invoice_bills_one_period_and_advances_the_next_invoice_date(): void
    {
        $contract = $this->makeSignedContract();

        $this->post(route('contracts.invoice', $contract))->assertRedirect();

        $invoice = Invoice::firstOrFail();
        $contract->refresh();

        $this->assertSame('8000000.00', $invoice->subtotal);
        $this->assertSame('2026-02-01', $contract->next_invoice_date->toDateString());
        $this->assertSame($contract->id, $invoice->contract_id);
        $this->assertNotNull($invoice->billing_snapshot);
    }

    public function test_unsigned_contract_cannot_be_invoiced(): void
    {
        $contract = $this->makeSignedContract();
        $contract->update(['status' => ContractStatus::Draft]);

        $this->post(route('contracts.invoice', $contract))->assertRedirect();

        $this->assertSame(0, Invoice::count());
    }

    public function test_a_retainer_invoice_without_a_mou_is_rejected(): void
    {
        $client = $this->makeClient();

        $this->post(route('invoices.store'), [
            ...$this->invoicePayload($client),
            'type' => 'recurring',
        ])->assertSessionHasErrors('contract_id');

        $this->assertNull(Invoice::first());
    }

    public function test_other_invoice_types_may_stand_without_a_mou(): void
    {
        $client = $this->makeClient();

        foreach (['invoice', 'down_payment', 'proforma'] as $type) {
            $this->post(route('invoices.store'), [
                ...$this->invoicePayload($client),
                'type' => $type,
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Invoice::query()->count());
        $this->assertSame(0, Invoice::query()->whereNotNull('contract_id')->count());
    }

    public function test_the_invoice_form_marks_which_mou_is_a_retainer(): void
    {
        $contract = $this->makeSignedContract();

        $this->get(route('invoices.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('contracts.0.id', $contract->id)
                ->where('contracts.0.is_recurring', true));
    }

    public function test_invoice_applies_ppn_on_top_of_the_line_items(): void
    {
        $client = $this->makeClient();

        $this->post(route('invoices.store'), [
            'number' => 'INV/2026/01/0001',
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'discount_amount' => 0,
            'tax_percent' => 11,
            'status' => 'draft',
            'items' => [
                [
                    'name' => 'Social Media Management',
                    'quantity' => 1,
                    'unit' => 'bulan',
                    'unit_price' => 8_000_000,
                ],
            ],
        ])->assertRedirect();

        $invoice = Invoice::firstOrFail();

        $this->assertSame('8000000.00', $invoice->subtotal);
        $this->assertSame('880000.00', $invoice->tax_amount);
        $this->assertSame('8880000.00', $invoice->total);
        $this->assertSame('8880000.00', $invoice->balance_due);
    }

    public function test_recording_payments_moves_the_invoice_through_partial_then_paid(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('payments.store', $invoice), [
            'amount' => 5_000_000,
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame('3880000.00', $invoice->balance_due);

        $this->post(route('payments.store', $invoice), [
            'amount' => 3_880_000,
            'paid_at' => '2026-01-12',
            'method' => 'transfer',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame('0.00', $invoice->balance_due);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_sent_invoice_cannot_be_edited(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->put(route('invoices.update', $invoice), [
            'number' => $invoice->number,
            'client_id' => $invoice->client_id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'discount_amount' => 0,
            'tax_percent' => 11,
            'status' => 'sent',
            'items' => [
                ['name' => 'Diubah', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 1],
            ],
        ])->assertRedirect();

        $this->assertSame('8000000.00', $invoice->fresh()->subtotal);
    }

    public function test_visiting_the_edit_page_of_a_sent_invoice_redirects_back_with_a_toast(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->get(route('invoices.edit', $invoice))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertInertiaFlash('toast', [
                'type' => 'error',
                'message' => 'Invoice yang sudah dikirim tidak bisa diubah. Batalkan dulu bila perlu koreksi.',
            ]);
    }

    public function test_mou_document_renders_the_indonesian_clauses(): void
    {
        $contract = $this->makeSignedContract();

        $this->post(route('contracts.finalize', $contract))->assertRedirect();

        $body = (string) $contract->fresh()->body;

        $this->assertStringContainsString('RUANG LINGKUP KERJASAMA', $body);
        $this->assertStringContainsString('Pihak Pertama', $body);
        $this->assertStringContainsString('Pihak Kedua', $body);
        $this->assertStringContainsString('Rp96.000.000', $body);

        $this->get(route('contracts.print', $contract))->assertOk();
    }

    public function test_invoice_pdf_is_rendered_fresh_on_every_download(): void
    {
        Storage::fake(ArchiveDocumentPdf::DISK);

        $invoice = $this->makeSentInvoice();
        $path = 'invoice/'.Str::slug($invoice->number).'.pdf';

        Storage::disk(ArchiveDocumentPdf::DISK)->put($path, 'PDF LAMA');
        $invoice->forceFill(['file_path' => $path])->save();

        $this->get(route('invoices.pdf', $invoice))->assertOk();

        $pdf = Storage::disk(ArchiveDocumentPdf::DISK)->get($invoice->fresh()->file_path);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringNotContainsString('PDF LAMA', $pdf);
    }

    public function test_kanban_column_can_be_added_renamed_and_removed(): void
    {
        $this->post(route('lead-stages.store'), [
            'name' => 'Kirim Sampel',
            'color' => '#0ea5e9',
            'type' => 'open',
        ])->assertRedirect();

        $stage = LeadStage::where('name', 'Kirim Sampel')->firstOrFail();

        $this->put(route('lead-stages.update', $stage), [
            'name' => 'Kirim Sampel Produk',
            'color' => '#22d3ee',
            'type' => 'open',
        ])->assertRedirect();

        $this->assertSame('Kirim Sampel Produk', $stage->fresh()->name);

        $this->delete(route('lead-stages.destroy', $stage))->assertRedirect();
        $this->assertNull($stage->fresh());
    }

    public function test_kanban_column_holding_leads_cannot_be_removed(): void
    {
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();
        $this->makeLead($stage, 'PT Tetap Ada');

        $this->delete(route('lead-stages.destroy', $stage))->assertRedirect();

        $this->assertNotNull($stage->fresh());
    }

    public function test_service_used_in_a_document_is_deactivated_rather_than_deleted(): void
    {
        $contract = $this->makeSignedContract();
        $service = $contract->items()->firstOrFail()->servicePackage->service;

        $this->delete(route('services.destroy', $service))->assertRedirect();

        $this->assertFalse($service->fresh()->is_active);
        $this->assertNotNull($service->fresh());
    }

    public function test_service_is_saved_together_with_its_packages_and_points(): void
    {
        $this->post(route('services.store'), [
            'type' => 'umkm',
            'name' => 'Foto Produk',
            'is_active' => true,
            'packages' => [
                [
                    'name' => 'Basic',
                    'price' => 1_500_000,
                    'unit' => 'paket',
                    'billing_type' => 'per_project',
                    'is_active' => true,
                    'points' => [['label' => '10 Foto Produk'], ['label' => 'Edit Warna']],
                ],
                [
                    'name' => 'Pro',
                    'price' => 3_000_000,
                    'unit' => 'paket',
                    'billing_type' => 'per_project',
                    'is_active' => true,
                    'points' => [['label' => '25 Foto Produk']],
                ],
            ],
        ])->assertRedirect();

        $service = Service::where('name', 'Foto Produk')->firstOrFail();

        $this->assertSame(ServiceType::Umkm, $service->type);
        $this->assertCount(2, $service->packages);
        $this->assertSame(['Basic', 'Pro'], $service->packages->pluck('name')->all());
        $this->assertSame(
            ['10 Foto Produk', 'Edit Warna'],
            $service->packages->firstOrFail()->points->pluck('label')->all(),
        );
    }

    public function test_renaming_a_package_keeps_the_documents_that_use_it(): void
    {
        $contract = $this->makeSignedContract();
        $package = $contract->items()->firstOrFail()->servicePackage;
        $service = $package->service;

        $this->put(route('services.update', $service), [
            'type' => $service->type->value,
            'name' => $service->name,
            'is_active' => true,
            'packages' => [
                [
                    'id' => $package->id,
                    'name' => 'Silver Plus',
                    'price' => $package->price,
                    'unit' => $package->unit,
                    'billing_type' => $package->billing_type->value,
                    'is_active' => true,
                    'points' => [['label' => 'Poin baru']],
                ],
            ],
        ])->assertRedirect();

        $this->assertSame('Silver Plus', $package->fresh()->name);
        $this->assertSame($package->id, $contract->items()->firstOrFail()->service_package_id);
        $this->assertSame(['Poin baru'], $package->fresh()->points->pluck('label')->all());
    }

    public function test_package_removed_from_the_form_is_deactivated_when_a_document_uses_it(): void
    {
        $contract = $this->makeSignedContract();
        $package = $contract->items()->firstOrFail()->servicePackage;
        $service = $package->service;
        $other = $service->packages()->where('id', '!=', $package->id)->firstOrFail();

        $this->put(route('services.update', $service), [
            'type' => $service->type->value,
            'name' => $service->name,
            'is_active' => true,
            'packages' => [
                [
                    'name' => 'Paket Baru',
                    'price' => 1_000_000,
                    'unit' => 'bulan',
                    'billing_type' => 'monthly_retainer',
                    'is_active' => true,
                    'points' => [],
                ],
            ],
        ])->assertRedirect();

        $this->assertNotNull($package->fresh());
        $this->assertFalse($package->fresh()->is_active);
        $this->assertNull($other->fresh());
    }

    public function test_service_without_any_package_is_rejected(): void
    {
        $this->post(route('services.store'), [
            'type' => 'umkm',
            'name' => 'Layanan Tanpa Paket',
            'is_active' => true,
            'packages' => [],
        ])->assertSessionHasErrors('packages');

        $this->assertNull(Service::where('name', 'Layanan Tanpa Paket')->first());
    }

    public function test_client_mou_and_invoice_each_have_their_own_page(): void
    {
        $this->makeSignedContract();
        $this->makeSentInvoice();

        $this->get(route('clients.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('clients/index')
            ->has('clients.data', 2)
            ->missing('tab')
            ->missing('contracts')
            ->missing('invoices'));

        $this->get(route('contracts.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('contracts/index')
            ->has('contracts.data', 1)
            ->missing('clients'));

        $this->get(route('invoices.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('invoices/index')
            ->has('invoices.data', 1)
            ->has('summary')
            ->missing('clients'));
    }

    public function test_each_document_page_filters_with_its_own_status_enum(): void
    {
        $contract = $this->makeSignedContract();
        $invoice = $this->makeSentInvoice();

        $this->get(route('contracts.index', ['status' => ContractStatus::Signed->value]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('contracts.data', 1));

        $this->get(route('contracts.index', ['status' => ContractStatus::Draft->value]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('contracts.data', 0));

        $this->get(route('invoices.index', ['status' => InvoiceStatus::Sent->value]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('invoices.data', 1));

        $this->get(route('invoices.index', ['status' => InvoiceStatus::Paid->value]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('invoices.data', 0));

        $this->get(route('contracts.index', ['filter_client' => $contract->client_id]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('contracts.data', 1));

        $this->get(route('invoices.index', ['filter_client' => $invoice->client_id]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('invoices.data', 1));

        $this->get(route('contracts.index', ['filter_client' => $invoice->client_id]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('contracts.data', 0));
    }

    public function test_list_pages_send_the_counts_the_row_actions_rely_on(): void
    {
        $contract = $this->makeSignedContract();
        $this->post(route('contracts.invoice', $contract))->assertRedirect();

        $this->get(route('clients.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('clients.data.0.invoices_count')
            ->etc());

        $this->get(route('contracts.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('contracts.data.0.invoices_count', 1)
            ->etc());

        $this->get(route('invoices.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('invoices.data.0.payments_count', 0)
            ->etc());
    }

    public function test_deleting_a_document_returns_to_its_own_list_page(): void
    {
        $contract = $this->makeSignedContract();

        $this->delete(route('contracts.destroy', $contract))
            ->assertRedirect(route('contracts.index'));

        $invoice = $this->makeSentInvoice();

        $this->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));
    }

    public function test_deleting_a_row_keeps_the_filters_that_were_active_on_the_list(): void
    {
        $contract = $this->makeSignedContract();
        $contractList = route('contracts.index', [
            'filter_client' => $contract->client_id,
            'status' => ContractStatus::Signed->value,
            'sort' => 'value',
            'direction' => 'desc',
        ]);

        $this->from($contractList)
            ->delete(route('contracts.destroy', $contract))
            ->assertRedirect($contractList);

        $invoice = $this->makeSentInvoice();
        $invoiceList = route('invoices.index', ['filter_client' => $invoice->client_id]);

        $this->from($invoiceList)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect($invoiceList);
    }

    public function test_deleting_from_the_detail_page_still_lands_on_the_plain_list(): void
    {
        $contract = $this->makeSignedContract();

        $this->from(route('contracts.show', $contract))
            ->delete(route('contracts.destroy', $contract))
            ->assertRedirect(route('contracts.index'));
    }

    public function test_the_filtered_client_stays_in_the_dropdown_after_its_last_document_is_deleted(): void
    {
        $contract = $this->makeSignedContract();
        $clientId = $contract->client_id;

        $contract->delete();

        $this->get(route('contracts.index', ['filter_client' => $clientId]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('contracts.data', 0)
                ->where('filters.filterClient', $clientId)
                ->has('filterClients', 1)
                ->where('filterClients.0.id', $clientId));

        $invoice = $this->makeSentInvoice();
        $invoiceClientId = $invoice->client_id;

        $invoice->delete();

        $this->get(route('invoices.index', ['filter_client' => $invoiceClientId]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.filterClient', $invoiceClientId)
                ->where('filterClients.0.id', $invoiceClientId));
    }

    public function test_a_page_number_past_the_end_falls_back_to_the_last_page_that_has_rows(): void
    {
        $client = $this->makeClient();

        foreach (range(1, 3) as $index) {
            Client::create([
                'company_name' => "PT Halaman {$index}",
                'contact_name' => "PIC {$index}",
            ]);
        }

        $this->get(route('clients.index', ['page' => 9]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('clients.data', 4));

        $this->assertNotNull($client->fresh());
    }

    public function test_filtering_by_a_client_that_no_longer_exists_is_dropped(): void
    {
        $client = $this->makeClient();
        $other = Client::create(['company_name' => 'PT Masih Ada', 'contact_name' => 'PIC Ada']);

        $client->delete();

        $this->get(route('clients.index', ['filter_client' => $client->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.filterClient', null)
                ->has('clients.data', 1)
                ->where('clients.data.0.id', $other->id));
    }

    public function test_all_index_pages_render(): void
    {
        foreach (['leads.index', 'clients.index', 'contracts.index', 'invoices.index', 'services.index', 'company.edit', 'dashboard'] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_leads_clients_services_and_payments_have_no_separate_create_edit_routes(): void
    {
        $removed = [
            'leads.create', 'leads.edit',
            'lead-stages.create', 'lead-stages.edit',
            'services.create', 'services.edit',
            'clients.create', 'clients.edit',
            'payments.create',
        ];

        foreach ($removed as $name) {
            $this->assertFalse(Route::has($name), "Rute {$name} seharusnya sudah dihapus.");
        }
    }

    public function test_contracts_and_invoices_have_their_own_create_and_edit_routes(): void
    {
        foreach (['contracts.create', 'contracts.edit', 'invoices.create', 'invoices.edit'] as $name) {
            $this->assertTrue(Route::has($name), "Rute {$name} seharusnya ada.");
        }
    }

    public function test_list_and_detail_pages_carry_the_props_their_modals_need(): void
    {
        $client = $this->makeClient();
        $contract = $this->makeSignedContract();
        $invoice = $this->makeSentInvoice();
        $draftInvoice = $this->makeDraftInvoice();

        $this->get(route('leads.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('users')->has('sources')->has('statuses')->has('stageTypes'));

        $this->get(route('clients.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('users'));

        $this->get(route('clients.show', $client))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('clients/show')
            ->has('users'));

        $this->get(route('services.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('types')->has('billingTypes'));

        $this->get(route('contracts.create'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('clients')->has('services')->has('company')->has('types')->has('billingCycles'));

        $this->get(route('contracts.edit', $contract))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('contract')->has('clients')->has('services')->has('company')->has('types')->has('billingCycles'));

        $this->get(route('invoices.create'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('clients')->has('contracts')->has('services')->has('company')->has('types'));

        $this->get(route('invoices.edit', $draftInvoice))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('invoice')->has('clients')->has('contracts')->has('services')->has('company')->has('types'));

        $this->get(route('contracts.show', $contract))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('types')->has('statuses')->has('billingCycles'));

        $this->get(route('invoices.show', $invoice))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('methods')->has('statuses'));
    }

    public function test_visiting_a_deleted_record_redirects_back_with_a_toast(): void
    {
        $client = $this->makeClient();
        $client->delete();

        $this->get(route('clients.show', $client->id))
            ->assertRedirect(route('dashboard'))
            ->assertInertiaFlash('toast', [
                'type' => 'error',
                'message' => 'Data yang dicari sudah tidak ada, mungkin sudah dihapus.',
            ]);
    }

    public function test_visiting_a_deleted_record_returns_to_wherever_the_user_came_from(): void
    {
        $client = $this->makeClient();
        $client->delete();

        $this->get(route('clients.index'));

        $this->get(route('clients.show', $client->id))
            ->assertRedirect(route('clients.index'));
    }

    public function test_a_missing_record_on_a_json_request_still_gets_a_plain_404(): void
    {
        $this->getJson(route('clients.show', 999_999))->assertNotFound();
    }

    public function test_contracts_list_can_be_filtered_by_client(): void
    {
        $first = $this->makeSignedContract();
        $second = $this->makeSignedContract();

        $this->get(route('contracts.index', ['filter_client' => $first->client_id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('contracts.data', 1)
                ->where('contracts.data.0.id', $first->id));

        $this->get(route('contracts.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('filterClients', 2));

        $this->assertNotSame($first->client_id, $second->client_id);
    }

    public function test_contracts_list_can_be_sorted_by_value(): void
    {
        $client = $this->makeClient();

        $cheap = Contract::create([
            'number' => 'MOU/CHEAP',
            'client_id' => $client->id,
            'type' => 'mou',
            'title' => 'Kontrak murah',
            'tax_percent' => 0,
            'billing_cycle' => 'one_time',
            'status' => ContractStatus::Draft,
            'signed_date' => '2026-01-01',
        ]);
        $cheap->items()->create([
            'name' => 'Item murah', 'quantity' => 1, 'unit' => 'paket',
            'unit_price' => 1_000_000, 'amount' => 1_000_000,
        ]);
        $cheap->recalculate();

        $expensive = Contract::create([
            'number' => 'MOU/MAHAL',
            'client_id' => $client->id,
            'type' => 'mou',
            'title' => 'Kontrak mahal',
            'tax_percent' => 0,
            'billing_cycle' => 'one_time',
            'status' => ContractStatus::Draft,
            'signed_date' => '2026-01-01',
        ]);
        $expensive->items()->create([
            'name' => 'Item mahal', 'quantity' => 1, 'unit' => 'paket',
            'unit_price' => 9_000_000, 'amount' => 9_000_000,
        ]);
        $expensive->recalculate();

        $this->get(route('contracts.index', ['sort' => 'value', 'direction' => 'asc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('contracts.data.0.title', 'Kontrak murah')
                ->where('contracts.data.1.title', 'Kontrak mahal'));

        $this->get(route('contracts.index', ['sort' => 'value', 'direction' => 'desc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('contracts.data.0.title', 'Kontrak mahal')
                ->where('contracts.data.1.title', 'Kontrak murah'));
    }

    public function test_invoices_list_can_be_filtered_by_client(): void
    {
        $first = $this->makeSentInvoice();
        $second = $this->makeSentInvoice();

        $this->get(route('invoices.index', ['filter_client' => $first->client_id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('invoices.data', 1)
                ->where('invoices.data.0.id', $first->id));

        $this->get(route('invoices.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('filterClients', 2));

        $this->assertNotSame($first->client_id, $second->client_id);
    }

    public function test_invoices_list_can_be_sorted_by_total(): void
    {
        $client = $this->makeClient();

        $cheap = Invoice::create([
            'number' => 'INV/CHEAP',
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'tax_percent' => 0,
            'status' => InvoiceStatus::Draft,
            'billing_snapshot' => $client->billingSnapshot(),
        ]);
        $cheap->items()->create([
            'name' => 'Item murah', 'quantity' => 1, 'unit' => 'paket',
            'unit_price' => 1_000_000, 'amount' => 1_000_000,
        ]);
        $cheap->recalculate();

        $expensive = Invoice::create([
            'number' => 'INV/MAHAL',
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'tax_percent' => 0,
            'status' => InvoiceStatus::Draft,
            'billing_snapshot' => $client->billingSnapshot(),
        ]);
        $expensive->items()->create([
            'name' => 'Item mahal', 'quantity' => 1, 'unit' => 'paket',
            'unit_price' => 9_000_000, 'amount' => 9_000_000,
        ]);
        $expensive->recalculate();

        $this->get(route('invoices.index', ['sort' => 'total', 'direction' => 'asc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('invoices.data.0.number', 'INV/CHEAP')
                ->where('invoices.data.1.number', 'INV/MAHAL'));

        $this->get(route('invoices.index', ['sort' => 'total', 'direction' => 'desc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('invoices.data.0.number', 'INV/MAHAL')
                ->where('invoices.data.1.number', 'INV/CHEAP'));
    }

    public function test_client_tab_can_be_sorted_by_company_name(): void
    {
        Client::create(['company_name' => 'PT Zebra', 'contact_name' => 'PIC Zebra']);
        Client::create(['company_name' => 'PT Awal', 'contact_name' => 'PIC Awal']);

        $this->get(route('clients.index', ['sort' => 'company_name', 'direction' => 'asc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('clients.data.0.company_name', 'PT Awal')
                ->where('clients.data.1.company_name', 'PT Zebra'));

        $this->get(route('clients.index', ['sort' => 'company_name', 'direction' => 'desc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('clients.data.0.company_name', 'PT Zebra')
                ->where('clients.data.1.company_name', 'PT Awal'));
    }

    public function test_mou_and_invoice_pages_only_offer_clients_that_have_documents_in_the_filter(): void
    {
        $this->makeSignedContract();
        $this->makeSentInvoice();
        $this->makeClient();

        $this->get(route('contracts.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('filterClients', 1));

        $this->get(route('invoices.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('filterClients', 1));

        $this->get(route('clients.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('filterClients', 3));
    }

    public function test_client_tab_can_be_filtered_by_a_specific_client(): void
    {
        $first = $this->makeClient();
        $second = Client::create(['company_name' => 'PT Kedua', 'contact_name' => 'PIC Kedua']);

        $this->get(route('clients.index', ['filter_client' => $first->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('clients.data', 1)
                ->where('clients.data.0.id', $first->id));

        $this->assertNotSame($first->id, $second->id);
    }

    public function test_an_invalid_status_value_is_ignored_instead_of_filtering_everything_out(): void
    {
        $this->makeClient();

        $this->get(route('clients.index', ['status' => "'; drop table clients; --"]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.status', '')
                ->has('clients.data', 1));

        $this->assertSame(1, Client::query()->count());
    }

    public function test_an_unknown_sort_column_is_ignored_instead_of_breaking_the_query(): void
    {
        $this->makeClient();

        $this->get(route('clients.index', ['sort' => 'id); drop table clients; --']))
            ->assertOk();

        $this->get(route('contracts.index', ['sort' => 'not_a_real_column']))
            ->assertOk();

        $this->get(route('invoices.index', ['sort' => 'not_a_real_column']))
            ->assertOk();
    }

    public function test_leads_page_defaults_to_the_kanban_tab_with_stage_options_always_present(): void
    {
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();
        $this->makeLead($stage, 'PT Kanban');

        $this->get(route('leads.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('tab', 'kanban')
                ->has('stages')
                ->missing('leads')
                ->has('stageOptions'));

        $this->get(route('leads.index', ['tab' => 'table']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('tab', 'table')
                ->has('leads')
                ->missing('stages')
                ->has('stageOptions'));
    }

    public function test_leads_table_tab_can_be_filtered_by_stage(): void
    {
        $prospek = LeadStage::where('slug', 'prospek-baru')->firstOrFail();
        $negosiasi = LeadStage::where('slug', 'negosiasi')->firstOrFail();

        $inProspek = $this->makeLead($prospek, 'PT Prospek');
        $this->makeLead($negosiasi, 'PT Negosiasi');

        $this->get(route('leads.index', ['tab' => 'table', 'filter_stage' => $prospek->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.id', $inProspek->id));
    }

    public function test_leads_table_tab_can_be_sorted_by_estimated_value(): void
    {
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        Lead::create([
            'lead_stage_id' => $stage->id, 'company_name' => 'PT Murah',
            'contact_name' => 'PIC Murah', 'estimated_value' => 1_000_000,
            'status' => LeadStatus::Open, 'position' => 0,
        ]);
        Lead::create([
            'lead_stage_id' => $stage->id, 'company_name' => 'PT Mahal',
            'contact_name' => 'PIC Mahal', 'estimated_value' => 9_000_000,
            'status' => LeadStatus::Open, 'position' => 1,
        ]);

        $this->get(route('leads.index', ['tab' => 'table', 'sort' => 'estimated_value', 'direction' => 'asc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('leads.data.0.company_name', 'PT Murah')
                ->where('leads.data.1.company_name', 'PT Mahal'));

        $this->get(route('leads.index', ['tab' => 'table', 'sort' => 'estimated_value', 'direction' => 'desc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('leads.data.0.company_name', 'PT Mahal')
                ->where('leads.data.1.company_name', 'PT Murah'));
    }

    public function test_leads_table_tab_can_be_sorted_by_stage_position(): void
    {
        $prospek = LeadStage::where('slug', 'prospek-baru')->firstOrFail();
        $negosiasi = LeadStage::where('slug', 'negosiasi')->firstOrFail();

        $this->assertTrue($prospek->position < $negosiasi->position);

        $this->makeLead($negosiasi, 'PT Negosiasi');
        $this->makeLead($prospek, 'PT Prospek');

        $this->get(route('leads.index', ['tab' => 'table', 'sort' => 'stage', 'direction' => 'asc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('leads.data.0.company_name', 'PT Prospek')
                ->where('leads.data.1.company_name', 'PT Negosiasi'));
    }

    public function test_leads_table_tab_ignores_invalid_status_and_sort_values(): void
    {
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();
        $this->makeLead($stage, 'PT Aman');

        $this->get(route('leads.index', ['tab' => 'table', 'status' => "'; drop table leads; --"]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.status', '')
                ->has('leads.data', 1));

        $this->get(route('leads.index', ['tab' => 'table', 'sort' => 'id); drop table leads; --']))
            ->assertOk();

        $this->assertSame(1, Lead::query()->count());
    }

    private function makeLead(LeadStage $stage, string $company, int $position = 0): Lead
    {
        return Lead::create([
            'lead_stage_id' => $stage->id,
            'company_name' => $company,
            'contact_name' => 'PIC '.$company,
            'estimated_value' => 10_000_000,
            'status' => LeadStatus::Open,
            'position' => $position,
        ]);
    }

    private function makeClient(): Client
    {
        return Client::create([
            'company_name' => 'PT Kopi Nusantara',
            'address' => 'Jl. Sudirman No. 1',
            'city' => 'Jakarta',
            'contact_name' => 'Dewi Lestari',
            'contact_position' => 'Marketing Manager',
        ]);
    }

    private function makeSignedContract(): Contract
    {
        $client = $this->makeClient();
        $package = $this->makeServicePackage();

        $contract = Contract::create([
            'number' => 'MOU/'.uniqid(),
            'client_id' => $client->id,
            'type' => 'mou',
            'title' => 'Pengelolaan Social Media 12 Bulan',
            'tax_percent' => 11,
            'billing_cycle' => 'monthly',
            'next_invoice_date' => '2026-01-01',
            'status' => ContractStatus::Signed,
            'signed_date' => '2026-01-01',
            'signing_place' => 'Jakarta',
        ]);

        $contract->items()->create([
            'service_package_id' => $package->id,
            'name' => $package->name,
            'quantity' => 12,
            'unit' => 'bulan',
            'unit_price' => 8_000_000,
            'amount' => 96_000_000,
        ]);

        $contract->recalculate();

        return $contract->fresh();
    }

    private function makeSentInvoice(): Invoice
    {
        return $this->makeInvoiceWithStatus(InvoiceStatus::Sent);
    }

    private function makeDraftInvoice(): Invoice
    {
        return $this->makeInvoiceWithStatus(InvoiceStatus::Draft);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayload(Client $client): array
    {
        return [
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'discount_amount' => 0,
            'tax_percent' => 11,
            'status' => 'draft',
            'items' => [
                [
                    'name' => 'Social Media Management',
                    'quantity' => 1,
                    'unit' => 'bulan',
                    'unit_price' => 8_000_000,
                ],
            ],
        ];
    }

    private function makeInvoiceWithStatus(InvoiceStatus $status): Invoice
    {
        $client = $this->makeClient();

        $invoice = Invoice::create([
            'number' => 'INV/'.uniqid(),
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'tax_percent' => 11,
            'status' => $status,
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
}
