<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Support\Crm\AgendaCalendar;
use App\Support\Crm\DashboardAttention;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_every_module_with_something_to_look_at(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertGreaterThanOrEqual(4, User::query()->count());
        $this->assertGreaterThanOrEqual(7, Lead::query()->count());
        $this->assertGreaterThanOrEqual(3, Client::query()->count());
        $this->assertGreaterThanOrEqual(3, Contract::query()->count());
        $this->assertGreaterThanOrEqual(5, Invoice::query()->count());
    }

    public function test_the_team_covers_every_role_including_one_still_waiting(): void
    {
        $this->seed(DemoDataSeeder::class);

        foreach ([UserRole::Administrator, UserRole::Manager, UserRole::Member] as $role) {
            $this->assertTrue(
                User::query()->where('role', $role)->whereNotNull('approved_at')->exists(),
                "tidak ada anggota dengan peran {$role->value}",
            );
        }

        $this->assertTrue(User::query()->whereNull('approved_at')->exists());
    }

    public function test_every_record_has_someone_behind_it(): void
    {
        $this->seed(DemoDataSeeder::class);

        foreach ([Lead::class, Client::class, Contract::class, Invoice::class] as $model) {
            $this->assertSame(
                0,
                $model::query()->doesntHave('assignees')->count(),
                "{$model} masih ada yang tanpa penanggung jawab",
            );
        }
    }

    public function test_the_crm_dashboard_data_is_not_empty(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertNotSame([], DashboardAttention::attention());
        $this->assertNotSame([], DashboardAttention::aging());
        $this->assertNotSame([], DashboardAttention::activities());
    }

    public function test_the_invoice_list_holds_every_status_worth_seeing(): void
    {
        $this->seed(DemoDataSeeder::class);

        foreach ([InvoiceStatus::Draft, InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid, InvoiceStatus::Paid] as $status) {
            $this->assertTrue(
                Invoice::query()->where('status', $status)->exists(),
                "tidak ada invoice berstatus {$status->value}",
            );
        }

        $this->assertTrue(
            Invoice::query()->overdue()->exists(),
            'tidak ada tagihan yang lewat jatuh tempo',
        );
    }

    public function test_a_mou_is_waiting_for_a_signature_and_another_is_about_to_end(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertTrue(
            Contract::query()->whereIn('status', [
                ContractStatus::Draft, ContractStatus::Review, ContractStatus::Sent,
            ])->exists(),
        );

        $this->assertTrue(Contract::query()->expiringWithin(60)->exists());
    }

    public function test_the_calendar_has_something_in_the_month_ahead(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertNotSame([], AgendaCalendar::events(now()->startOfMonth(), AgendaCalendar::TYPES));
    }

    public function test_running_it_twice_does_not_double_the_data(): void
    {
        $this->seed(DemoDataSeeder::class);

        $before = [
            'users' => User::query()->count(),
            'leads' => Lead::query()->count(),
            'clients' => Client::query()->count(),
            'contracts' => Contract::query()->count(),
            'invoices' => Invoice::query()->count(),
        ];

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($before, [
            'users' => User::query()->count(),
            'leads' => Lead::query()->count(),
            'clients' => Client::query()->count(),
            'contracts' => Contract::query()->count(),
            'invoices' => Invoice::query()->count(),
        ]);
    }
}
