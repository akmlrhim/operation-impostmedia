<?php

namespace Tests\Feature\Access;

use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $superuser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);

        $this->manager = User::factory()->create(['role' => UserRole::Manager]);
        $this->superuser = User::factory()->create(['role' => UserRole::Superuser]);
    }

    public function test_the_role_enum_only_offers_three_roles(): void
    {
        $this->assertSame(
            ['superuser', 'manager', 'member'],
            array_column(UserRole::cases(), 'value'),
        );
    }

    public function test_a_member_is_kept_out_of_master_data_routes(): void
    {
        $member = $this->member();

        foreach ([
            'services.index' => 'GET',
            'company.edit' => 'GET',
            'lead-stages.store' => 'POST',
            'lead-stages.reorder' => 'POST',
            'services.store' => 'POST',
        ] as $name => $method) {
            $this->actingAs($member)->call($method, route($name))->assertForbidden();
        }
    }

    public function test_a_member_is_kept_out_of_document_approval_routes(): void
    {
        $member = $this->member();
        $contract = $this->makeContract();
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();
        $lead = $this->makeLead($stage, 'PT Milik Member', $member);

        $this->actingAs($member);

        $this->post(route('contracts.sign', $contract))->assertForbidden();
        $this->post(route('contracts.finalize', $contract))->assertForbidden();
        $this->post(route('contracts.invoice', $contract))->assertForbidden();
        $this->post(route('contracts.clauses', $contract))->assertForbidden();
        $this->post(route('contracts.scope-points'))->assertForbidden();
        $this->post(route('leads.convert', $lead))->assertForbidden();
    }

    public function test_a_member_is_kept_out_of_everything_financial(): void
    {
        $member = $this->member();
        $invoice = $this->makeInvoice();

        $this->actingAs($member);

        $this->get(route('finance.dashboard'))->assertForbidden();
        $this->get(route('finance.index'))->assertForbidden();
        $this->post(route('invoices.send', $invoice))->assertForbidden();
        $this->post(route('invoices.settle', $invoice))->assertForbidden();
        $this->post(route('invoices.void', $invoice))->assertForbidden();
        $this->post(route('payments.store', $invoice))->assertForbidden();
    }

    public function test_a_member_cannot_delete_or_export_any_record(): void
    {
        $member = $this->member();
        $lead = $this->makeLead(LeadStage::where('slug', 'prospek-baru')->firstOrFail(), 'PT Milik Member', $member);
        $contract = $this->makeContract();
        $invoice = $this->makeInvoice();

        $this->actingAs($member);

        $this->delete(route('leads.destroy', $lead))->assertForbidden();
        $this->delete(route('clients.destroy', $contract->client_id))->assertForbidden();
        $this->delete(route('contracts.destroy', $contract))->assertForbidden();
        $this->delete(route('invoices.destroy', $invoice))->assertForbidden();
        $this->get(route('leads.export'))->assertForbidden();
        $this->get(route('clients.export'))->assertForbidden();
        $this->get(route('contracts.export'))->assertForbidden();
        $this->get(route('invoices.export'))->assertForbidden();
    }

    public function test_a_member_cannot_edit_an_invoice_even_their_own_draft(): void
    {
        $member = $this->member();
        $invoice = $this->makeInvoice();
        $invoice->update(['created_by' => $member->id]);

        $this->actingAs($member);

        $this->get(route('invoices.edit', $invoice))->assertForbidden();
        $this->put(route('invoices.update', $invoice), [
            'client_id' => $invoice->client_id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'total' => 1_000_000,
            'status' => 'draft',
        ])->assertForbidden();
    }

    public function test_a_member_only_sees_and_reaches_the_leads_that_are_theirs(): void
    {
        $member = $this->member();
        $other = $this->member();
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        $theirs = $this->makeLead($stage, 'PT Milik Member', $member);
        $others = $this->makeLead($stage, 'PT Milik Orang Lain', $other);

        $this->actingAs($member);

        $this->get(route('leads.index', ['tab' => 'table']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('total', 1));

        $this->get(route('leads.show', $theirs))->assertOk();
        $this->get(route('leads.show', $others))->assertNotFound();
    }

    public function test_a_lead_assigned_to_a_member_is_visible_to_that_member(): void
    {
        $member = $this->member();
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        $lead = $this->makeLead($stage, 'PT Ditugaskan', $this->manager);
        $lead->assignees()->sync([$member->id]);

        $this->actingAs($member);

        $this->get(route('leads.index', ['tab' => 'table']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('total', 1));

        $this->get(route('leads.show', $lead))->assertOk();
    }

    public function test_an_outsider_cannot_update_another_members_lead(): void
    {
        $member = $this->member();
        $other = $this->member();
        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        $lead = $this->makeLead($stage, 'PT Rahasia', $other);

        $this->actingAs($member)
            ->put(route('leads.update', $lead), [
                'lead_stage_id' => $stage->id,
                'company_name' => 'PT Dibajak',
                'contact_name' => 'Peretas',
                'estimated_value' => 10_000_000,
                'status' => 'open',
            ])->assertNotFound();

        $this->assertSame('PT Rahasia', $lead->fresh()->company_name);
    }

    public function test_the_administrator_role_is_no_longer_acceptable(): void
    {
        $pending = User::factory()->pending()->create();

        $this->actingAs($this->superuser)
            ->post(route('users.approve', $pending), [
                'role' => 'administrator',
                'is_active' => true,
            ])->assertSessionHasErrors('role');

        $this->assertNull($pending->fresh()->approved_at);
    }

    public function test_a_manager_reaches_every_gate_a_superuser_can_except_users(): void
    {
        $this->actingAs($this->manager);

        $this->get(route('services.index'))->assertOk();
        $this->get(route('company.edit'))->assertOk();
        $this->get(route('finance.dashboard'))->assertOk();
        $this->get(route('users.index'))->assertForbidden();

        $this->actingAs($this->superuser)->get(route('users.index'))->assertOk();
    }

    private function member(): User
    {
        return User::factory()->create(['role' => UserRole::Member]);
    }

    private function makeLead(LeadStage $stage, string $name, User $owner): Lead
    {
        return Lead::create([
            'lead_stage_id' => $stage->id,
            'company_name' => $name,
            'contact_name' => 'Budi',
            'estimated_value' => 10_000_000,
            'status' => 'open',
            'position' => 0,
            'created_by' => $owner->id,
        ]);
    }

    private function makeContract(): Contract
    {
        $client = Client::create([
            'company_name' => 'PT Kontrakan',
            'contact_name' => 'Rani',
            'contact_position' => 'Direktur',
            'created_by' => $this->manager->id,
        ]);

        return Contract::create([
            'number' => 'MOU/AKSES',
            'client_id' => $client->id,
            'type' => 'mou',
            'title' => 'MoU uji akses',
            'tax_percent' => 0,
            'billing_cycle' => 'one_time',
            'status' => ContractStatus::Draft,
            'created_by' => $this->manager->id,
        ]);
    }

    private function makeInvoice(): Invoice
    {
        $client = Client::create([
            'company_name' => 'PT Ditagih',
            'contact_name' => 'Rani',
            'contact_position' => 'Direktur',
            'created_by' => $this->manager->id,
        ]);

        return Invoice::create([
            'number' => 'INV/AKSES',
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'tax_percent' => 0,
            'status' => InvoiceStatus::Draft,
            'created_by' => $this->manager->id,
        ]);
    }
}
