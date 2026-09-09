<?php

namespace Tests\Feature\Crm;

use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_client_can_be_deleted_and_its_documents_stay_as_orphans(): void
    {
        $client = $this->client();
        $contract = $this->contract($client);
        $invoice = $this->invoice($client, $contract);

        $this->delete(route('clients.destroy', $client))->assertRedirect();

        $this->assertNull(Client::find($client->id));
        $this->assertNotNull($contract->fresh());
        $this->assertNull($contract->fresh()->client_id);
        $this->assertNotNull($invoice->fresh());
        $this->assertNull($invoice->fresh()->client_id);
    }

    public function test_contract_can_be_deleted_even_when_it_has_invoices(): void
    {
        $client = $this->client();
        $contract = $this->contract($client);
        $invoice = $this->invoice($client, $contract);

        $this->delete(route('contracts.destroy', $contract))->assertRedirect();

        $this->assertNull(Contract::find($contract->id));
        $this->assertNotNull($invoice->fresh());
        $this->assertNull($invoice->fresh()->contract_id);
        $this->assertSame($client->id, $invoice->fresh()->client_id);
    }

    public function test_invoice_can_be_deleted_even_when_payments_are_recorded(): void
    {
        $invoice = $this->invoice($this->client());

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 1_000_000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $this->delete(route('invoices.destroy', $invoice))->assertRedirect();

        $this->assertNull(Invoice::find($invoice->id));
    }

    public function test_orphan_documents_are_listed_under_their_own_group(): void
    {
        $client = $this->client();
        $contract = $this->contract($client);
        $this->invoice($client, $contract);

        $this->delete(route('clients.destroy', $client))->assertRedirect();

        $this->get(route('contracts.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('groups.data', 0)
                ->where('orphans.id', null)
                ->where('orphans.company_name', 'Tanpa klien')
                ->where('orphans.contracts_count', 1)
                ->where('orphans.contracts_value', 5000000)
                ->has('orphans.contracts', 1)
                ->etc());

        $this->get(route('invoices.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('groups.data', 0)
                ->where('orphans.company_name', 'Tanpa klien')
                ->where('orphans.invoices_count', 1)
                ->has('orphans.invoices', 1)
                ->etc());
    }

    public function test_a_mou_without_a_client_cannot_be_invoiced(): void
    {
        $client = $this->client();
        $contract = $this->contract($client);

        $this->delete(route('clients.destroy', $client))->assertRedirect();

        $this->post(route('contracts.invoice', $contract))->assertRedirect();

        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_an_orphan_contract_still_renders_its_document(): void
    {
        $client = $this->client();
        $contract = $this->contract($client);

        $this->delete(route('clients.destroy', $client))->assertRedirect();

        $this->post(route('contracts.finalize', $contract))->assertRedirect();

        $this->assertNotNull($contract->fresh()->renderedBody());
    }

    public function test_service_used_in_a_document_can_still_be_deleted(): void
    {
        $service = Service::create([
            'type' => 'brand',
            'name' => 'Social Media Management',
            'is_active' => true,
        ]);

        $package = $service->packages()->create([
            'name' => 'Silver',
            'price' => 5_000_000,
            'unit' => 'bulan',
            'billing_type' => 'monthly_retainer',
            'is_active' => true,
        ]);

        $contract = $this->contract($this->client());
        $item = $contract->items()->firstOrFail();
        $item->update(['service_package_id' => $package->id]);

        $this->delete(route('services.destroy', $service))->assertRedirect();

        $this->assertNull(Service::find($service->id));
        $this->assertNull(ServicePackage::find($package->id));
        $this->assertNotNull($item->fresh());
        $this->assertNull($item->fresh()->service_package_id);
        $this->assertSame('Social Media Management', $item->fresh()->name);
    }

    private function client(): Client
    {
        return Client::create([
            'company_name' => 'PT Kopi Nusantara',
            'contact_name' => 'Dewi Lestari',
            'contact_position' => 'Marketing Manager',
        ]);
    }

    private function contract(Client $client): Contract
    {
        $contract = Contract::create([
            'number' => 'MOU/HAPUS',
            'client_id' => $client->id,
            'type' => 'mou',
            'title' => 'Pengelolaan Social Media',
            'tax_percent' => 0,
            'billing_cycle' => 'one_time',
            'status' => ContractStatus::Signed,
            'signed_date' => '2026-01-01',
        ]);

        $contract->items()->create([
            'name' => 'Social Media Management', 'quantity' => 1, 'unit' => 'paket',
            'unit_price' => 5_000_000, 'amount' => 5_000_000,
        ]);

        $contract->recalculate();

        return $contract->fresh();
    }

    private function invoice(Client $client, ?Contract $contract = null): Invoice
    {
        $invoice = Invoice::create([
            'number' => 'INV/HAPUS',
            'client_id' => $client->id,
            'contract_id' => $contract?->id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'tax_percent' => 0,
            'status' => InvoiceStatus::Sent,
            'billing_snapshot' => $client->billingSnapshot(),
        ]);

        $invoice->items()->create([
            'name' => 'Social Media Management', 'quantity' => 1, 'unit' => 'paket',
            'unit_price' => 5_000_000, 'amount' => 5_000_000,
        ]);

        $invoice->recalculate();

        return $invoice->fresh();
    }
}
