<?php

namespace Tests\Feature\Finance;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\FinanceTransaction;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceIncomeRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_a_partial_payment_does_not_touch_the_ledger_yet(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('payments.store', $invoice), [
            'amount' => 5_000_000,
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
        ])->assertRedirect();

        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->fresh()->status);
        $this->assertDatabaseMissing('finance_transactions', ['invoice_id' => $invoice->id]);
    }

    public function test_settling_an_invoice_records_an_income_in_the_finance_module(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('payments.store', $invoice), [
            'amount' => '8880000',
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
        ])->assertRedirect();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);

        $this->assertDatabaseHas('finance_transactions', [
            'invoice_id' => $invoice->id,
            'type' => 'income',
            'category' => 'Pembayaran invoice',
            'amount' => '8880000.00',
            'notes' => "{$invoice->number} • PT Kopi Nusantara",
            'recorded_by' => auth()->id(),
        ]);
    }

    public function test_recording_more_payments_on_a_paid_invoice_does_not_duplicate_the_income(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('payments.store', $invoice), [
            'amount' => '8880000',
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
        ])->assertRedirect();

        $this->post(route('payments.store', $invoice), [
            'amount' => '100000',
            'paid_at' => '2026-01-11',
            'method' => 'transfer',
        ])->assertRedirect();

        $this->assertSame(1, FinanceTransaction::where('invoice_id', $invoice->id)->count());
    }

    public function test_deleting_a_payment_that_unsettles_the_invoice_removes_the_income(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('payments.store', $invoice), [
            'amount' => '8880000',
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
        ])->assertRedirect();

        $this->assertSame(1, FinanceTransaction::where('invoice_id', $invoice->id)->count());

        $payment = $invoice->payments()->firstOrFail();

        $this->delete(route('payments.destroy', $payment))->assertRedirect();

        $this->assertNotSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertDatabaseMissing('finance_transactions', ['invoice_id' => $invoice->id]);
    }

    public function test_the_settle_button_also_records_the_income(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('payments.store', $invoice), [
            'amount' => 5_000_000,
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
        ])->assertRedirect();

        $this->assertDatabaseMissing('finance_transactions', ['invoice_id' => $invoice->id]);

        $this->post(route('invoices.settle', $invoice))->assertRedirect();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertDatabaseHas('finance_transactions', [
            'invoice_id' => $invoice->id,
            'type' => 'income',
            'amount' => '8880000.00',
        ]);
    }

    public function test_a_payment_can_carry_a_transfer_proof(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('payments.store', $invoice), [
            'amount' => 5_000_000,
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
            'proof' => UploadedFile::fake()->image('bukti-transfer.png'),
        ])->assertRedirect();

        $payment = $invoice->payments()->firstOrFail();

        $this->assertNotNull($payment->proof_path);
        Storage::disk('local')->assertExists($payment->proof_path);

        $this->get(route('payments.proof', $payment))->assertOk();
    }

    public function test_settling_an_invoice_can_carry_a_transfer_proof(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('invoices.settle', $invoice), [
            'proof' => UploadedFile::fake()->image('bukti-lunas.png'),
        ])->assertRedirect();

        $payment = $invoice->payments()->firstOrFail();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('local')->assertExists($payment->proof_path);

        $this->get(route('payments.proof', $payment))->assertOk();
    }

    public function test_deleting_a_payment_removes_its_proof_file(): void
    {
        $invoice = $this->makeSentInvoice();

        $this->post(route('payments.store', $invoice), [
            'amount' => '8880000',
            'paid_at' => '2026-01-10',
            'method' => 'transfer',
            'proof' => UploadedFile::fake()->image('bukti-transfer.png'),
        ])->assertRedirect();

        $payment = $invoice->payments()->firstOrFail();

        $this->delete(route('payments.destroy', $payment))->assertRedirect();

        Storage::disk('local')->assertMissing($payment->proof_path);
    }

    private function makeSentInvoice(): Invoice
    {
        $client = Client::create([
            'company_name' => 'PT Kopi Nusantara',
            'short_code' => 'KPN',
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
