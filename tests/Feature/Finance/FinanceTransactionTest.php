<?php

namespace Tests\Feature\Finance;

use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_the_dashboard_shows_a_summary_and_chart_for_the_selected_month(): void
    {
        FinanceTransaction::create([
            'type' => 'income',
            'category' => 'Pembayaran klien',
            'amount' => 5_000_000,
            'transaction_date' => now()->startOfMonth()->addDays(2),
        ]);

        FinanceTransaction::create([
            'type' => 'expense',
            'category' => 'Sewa kantor',
            'amount' => 2_000_000,
            'transaction_date' => now()->startOfMonth()->addDays(3),
        ]);

        $this->get(route('finance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('summary.income', 5_000_000)
                ->where('summary.expense', 2_000_000)
                ->where('summary.net', 3_000_000)
                ->has('transactions.data', 2));
    }

    public function test_a_transaction_can_be_recorded(): void
    {
        $this->post(route('finance.store'), [
            'type' => 'expense',
            'category' => 'Listrik',
            'amount' => 750_000,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Tagihan bulan ini',
        ])->assertRedirect();

        $this->assertDatabaseHas('finance_transactions', [
            'type' => 'expense',
            'category' => 'Listrik',
            'amount' => '750000.00',
            'recorded_by' => auth()->id(),
        ]);
    }

    public function test_type_must_be_a_valid_enum_value(): void
    {
        $this->post(route('finance.store'), [
            'type' => 'not-a-type',
            'category' => 'Listrik',
            'amount' => 750_000,
            'transaction_date' => now()->toDateString(),
        ])->assertSessionHasErrors('type');
    }

    public function test_a_transaction_can_be_updated(): void
    {
        $transaction = FinanceTransaction::create([
            'type' => 'income',
            'category' => 'Pembayaran klien',
            'amount' => 1_000_000,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->put(route('finance.update', $transaction), [
            'type' => 'income',
            'category' => 'Pembayaran klien lama',
            'amount' => 1_500_000,
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('finance_transactions', [
            'id' => $transaction->id,
            'category' => 'Pembayaran klien lama',
            'amount' => '1500000.00',
        ]);
    }

    public function test_a_transaction_can_be_deleted(): void
    {
        $transaction = FinanceTransaction::create([
            'type' => 'expense',
            'category' => 'ATK',
            'amount' => 100_000,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->delete(route('finance.destroy', $transaction))->assertRedirect();

        $this->assertSoftDeleted($transaction);
    }
}
