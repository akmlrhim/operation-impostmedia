<?php

namespace Tests\Feature\Crm;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Crm\DashboardStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-08 09:00:00'));

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_dashboard_defaults_to_the_current_month(): void
    {
        $this->get(route('crm.dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('month', '2026-09')
                ->where('period', 'Sep 2026'));
    }

    public function test_stats_follow_the_selected_month(): void
    {
        $this->issueInvoice('INV/JUL', '2026-07-10', 4_000_000);
        $this->issueInvoice('INV/SEP', '2026-09-03', 9_000_000);

        $this->get(route('crm.dashboard', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('month', '2026-07')
                ->where('period', 'Jul 2026')
                ->where('stats.issued', 4_000_000));

        $this->get(route('crm.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stats.issued', 9_000_000));
    }

    public function test_trend_ends_on_the_selected_month(): void
    {
        $this->get(route('crm.dashboard', ['month' => '2026-07']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('trend', DashboardStats::TREND_MONTHS)
                ->where('trend.0.key', '2026-02')
                ->where('trend.5.key', '2026-07'));
    }

    public function test_selectable_months_run_from_the_oldest_record_to_now(): void
    {
        $this->issueInvoice('INV/MEI', '2026-05-20', 1_000_000);

        $this->get(route('crm.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('months', 5)
                ->where('months.0.value', '2026-09')
                ->where('months.0.label', 'Sep 2026')
                ->where('months.4.value', '2026-05'));
    }

    public function test_a_future_or_malformed_month_falls_back_to_the_current_month(): void
    {
        foreach (['2027-01', 'bulan-depan', '2026-13', ''] as $month) {
            $this->get(route('crm.dashboard', ['month' => $month]))
                ->assertOk()
                ->assertInertia(fn (AssertableInertia $page) => $page->where('month', '2026-09'));
        }
    }

    private function issueInvoice(string $number, string $issueDate, float $amount): Invoice
    {
        $client = Client::firstOrCreate(
            ['company_name' => 'PT Periode'],
            ['contact_name' => 'PIC Periode'],
        );

        $invoice = Invoice::create([
            'number' => $number,
            'client_id' => $client->id,
            'type' => 'invoice',
            'issue_date' => $issueDate,
            'due_date' => $issueDate,
            'tax_percent' => 0,
            'status' => InvoiceStatus::Sent,
            'billing_snapshot' => $client->billingSnapshot(),
        ]);

        $invoice->items()->create([
            'name' => 'Jasa', 'quantity' => 1, 'unit' => 'paket',
            'unit_price' => $amount, 'amount' => $amount,
        ]);

        $invoice->recalculate();

        return $invoice;
    }
}
