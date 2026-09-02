<?php

namespace Tests\Feature\Crm;

use App\Enums\InvoiceStatus;
use App\Events\CrmChanged;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\LeadStage;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeBroadcastTest extends TestCase
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

    public function test_creating_a_lead_announces_the_leads_topic(): void
    {
        Event::fake([CrmChanged::class]);

        $stage = LeadStage::where('slug', 'prospek-baru')->firstOrFail();

        $this->post(route('leads.store'), [
            'lead_stage_id' => $stage->id,
            'company_name' => 'PT Kopi Nusantara',
            'contact_name' => 'Dewi Lestari',
            'estimated_value' => 96_000_000,
            'priority' => 'high',
            'status' => 'open',
        ])->assertRedirect();

        Event::assertDispatched(
            CrmChanged::class,
            fn (CrmChanged $event): bool => $event->resource === 'leads' && $event->action === 'created',
        );
    }

    public function test_a_payment_announces_the_invoice_it_belongs_to(): void
    {
        $invoice = $this->makeSentInvoice();

        Event::fake([CrmChanged::class]);

        $this->post(route('payments.store', $invoice), [
            'amount' => 5_000_000,
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
        ])->assertRedirect();

        Event::assertDispatched(
            CrmChanged::class,
            fn (CrmChanged $event): bool => $event->resource === 'invoices',
        );

        Event::assertNotDispatched(
            CrmChanged::class,
            fn (CrmChanged $event): bool => $event->resource === 'payments',
        );
    }

    public function test_the_broadcast_carries_no_record_data(): void
    {
        $event = new CrmChanged('invoices', 'updated', 7);

        $this->assertSame(
            ['resource' => 'invoices', 'action' => 'updated', 'id' => 7],
            $event->broadcastWith(),
        );

        $this->assertSame('private-crm', $event->broadcastOn()->name);
        $this->assertSame('crm.changed', $event->broadcastAs());
    }

    private function makeSentInvoice(): Invoice
    {
        $client = Client::create([
            'company_name' => 'PT Rasa Nusantara',
            'contact_name' => 'Bagas Prakoso',
            'email' => 'bagas@rasanusantara.test',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'number' => 'INV/'.uniqid(),
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'tax_percent' => 11,
            'status' => InvoiceStatus::Sent,
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
