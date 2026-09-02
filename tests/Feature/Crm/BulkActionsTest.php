<?php

namespace Tests\Feature\Crm;

use App\Enums\ContractType;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_contracts_can_be_bulk_deleted_but_ones_with_invoices_are_skipped(): void
    {
        $client = $this->makeClient();
        $keep = $this->makeContract($client, 'IM-MOU-0101-PRJ-001');
        $remove = $this->makeContract($client, 'IM-MOU-0101-PRJ-002');

        Invoice::create($this->invoiceAttributes($client, $keep, 'IM-INV-0101-PRJ-001'));

        $this->delete(route('contracts.destroy-bulk'), ['ids' => [$keep->id, $remove->id]])
            ->assertRedirect();

        $this->assertNotNull(Contract::find($keep->id));
        $this->assertNull(Contract::find($remove->id));
    }

    public function test_contracts_can_be_exported_as_csv(): void
    {
        $client = $this->makeClient();
        $contract = $this->makeContract($client, 'IM-MOU-0101-PRJ-003');

        $response = $this->get(route('contracts.export', ['ids' => [$contract->id]]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString($contract->number, $response->streamedContent());
    }

    public function test_invoices_can_be_bulk_deleted_but_ones_with_payments_are_skipped(): void
    {
        $client = $this->makeClient();
        $keep = Invoice::create($this->invoiceAttributes($client, null, 'IM-INV-0101-PRJ-010'));
        $remove = Invoice::create($this->invoiceAttributes($client, null, 'IM-INV-0101-PRJ-011'));

        Payment::create([
            'invoice_id' => $keep->id,
            'amount' => 100_000,
            'paid_at' => now(),
            'method' => 'transfer',
        ]);

        $this->delete(route('invoices.destroy-bulk'), ['ids' => [$keep->id, $remove->id]])
            ->assertRedirect();

        $this->assertNotNull(Invoice::find($keep->id));
        $this->assertNull(Invoice::find($remove->id));
    }

    public function test_leads_can_be_bulk_deleted(): void
    {
        $stage = LeadStage::query()->firstOrFail();

        $first = Lead::create($this->leadAttributes($stage, 'PT Satu'));
        $second = Lead::create($this->leadAttributes($stage, 'PT Dua'));

        $this->delete(route('leads.destroy-bulk'), ['ids' => [$first->id, $second->id]])
            ->assertRedirect();

        $this->assertNull(Lead::find($first->id));
        $this->assertNull(Lead::find($second->id));
    }

    public function test_clients_can_be_bulk_deleted_but_ones_with_invoices_are_skipped(): void
    {
        $keep = $this->makeClient('PT Simpan');
        $remove = $this->makeClient('PT Hapus');

        Invoice::create($this->invoiceAttributes($keep, null, 'IM-INV-0101-PRJ-020'));

        $this->delete(route('clients.destroy-bulk'), ['ids' => [$keep->id, $remove->id]])
            ->assertRedirect();

        $this->assertNotNull(Client::find($keep->id));
        $this->assertNull(Client::find($remove->id));
    }

    public function test_bulk_delete_requires_at_least_one_id(): void
    {
        $this->delete(route('contracts.destroy-bulk'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    private function makeClient(string $name = 'PT Kopi Nusantara'): Client
    {
        return Client::create([
            'company_name' => $name,
            'address' => 'Jl. Melati 1, Banjarmasin',
            'contact_name' => 'Rani',
            'contact_position' => 'Direktur',
        ]);
    }

    private function makeContract(Client $client, string $number): Contract
    {
        $contract = Contract::create([
            'number' => $number,
            'client_id' => $client->id,
            'type' => ContractType::Mou,
            'title' => 'Pengelolaan Media Sosial',
            'signed_date' => '2026-06-06',
            'start_date' => '2026-06-06',
            'end_date' => '2026-09-06',
        ]);

        $contract->recalculate();

        return $contract->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceAttributes(Client $client, ?Contract $contract, string $number): array
    {
        return [
            'number' => $number,
            'client_id' => $client->id,
            'contract_id' => $contract?->id,
            'issue_date' => '2026-06-06',
            'due_date' => '2026-07-06',
            'total' => 1_000_000,
            'balance_due' => 1_000_000,
            'status' => 'sent',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leadAttributes(LeadStage $stage, string $name): array
    {
        return [
            'lead_stage_id' => $stage->id,
            'company_name' => $name,
            'contact_name' => 'Budi',
            'estimated_value' => 10_000_000,
            'priority' => 'medium',
            'status' => 'open',
            'position' => 0,
        ];
    }
}
