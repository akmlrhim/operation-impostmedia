<?php

namespace App\Http\Controllers\Finance;

use App\Enums\FinanceTransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\BulkIdsRequest;
use App\Http\Requests\Finance\FinanceTransactionRequest;
use App\Models\FinanceTransaction;
use App\Support\BulkDeleteSummary;
use App\Support\Crm\DashboardStats;
use App\Support\Csv\CsvExport;
use App\Support\EnumOptions;
use App\Support\Finance\FinanceIndexQuery;
use App\Support\Finance\FinanceStats;
use App\Support\ListRedirect;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    public function dashboard(Request $request): Response
    {
        [$mode, $monthStart, $from, $to] = $this->resolvePeriod($request);

        return Inertia::render('finance/dashboard', [
            'mode' => $mode,
            'month' => $monthStart->format('Y-m'),
            'months' => DashboardStats::months(),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'period' => $this->periodLabel($mode, $monthStart, $from, $to),
            'summary' => FinanceStats::summary($from, $to),
            'chart' => FinanceStats::series($from, $to),
        ]);
    }

    public function index(Request $request): Response
    {
        [$mode, $monthStart, $from, $to] = $this->resolvePeriod($request);

        return Inertia::render('finance/index', [
            ...FinanceIndexQuery::build($request, $from, $to),
            'types' => EnumOptions::from(FinanceTransactionType::class),
            'mode' => $mode,
            'month' => $monthStart->format('Y-m'),
            'months' => DashboardStats::months(),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => FinanceStats::summary($from, $to),
        ]);
    }

    public function store(FinanceTransactionRequest $request): RedirectResponse
    {
        FinanceTransaction::create([
            ...$request->validated(),
            'recorded_by' => auth()->id(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaksi keuangan dicatat.']);

        return back();
    }

    public function update(FinanceTransactionRequest $request, FinanceTransaction $financeTransaction): RedirectResponse
    {
        $financeTransaction->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaksi keuangan diperbarui.']);

        return back();
    }

    public function destroy(FinanceTransaction $financeTransaction): RedirectResponse
    {
        $financeTransaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaksi keuangan dihapus.']);

        return ListRedirect::to('finance.index');
    }

    public function destroyBulk(BulkIdsRequest $request): RedirectResponse
    {
        $transactions = FinanceTransaction::query()->whereIn('id', $request->ids())->get();

        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        Inertia::flash('toast', BulkDeleteSummary::toast($transactions->count(), 0, 'transaksi', ''));

        return ListRedirect::to('finance.index');
    }

    public function exportCsv(BulkIdsRequest $request): StreamedResponse
    {
        $transactions = FinanceTransaction::query()
            ->whereIn('id', $request->ids())
            ->orderBy('transaction_date')
            ->get();

        return CsvExport::download(
            'buku-kas-'.now()->format('Ymd-His').'.csv',
            ['Tanggal', 'Keterangan', 'Debit', 'Kredit'],
            $transactions->map(fn (FinanceTransaction $transaction): array => [
                $transaction->transaction_date->format('Y-m-d'),
                $transaction->category.($transaction->notes ? " ({$transaction->notes})" : ''),
                $transaction->type === FinanceTransactionType::Income ? (string) $transaction->amount : '',
                $transaction->type === FinanceTransactionType::Expense ? (string) $transaction->amount : '',
            ]),
        );
    }

    /**
     * @return array{0: 'month'|'range', 1: CarbonInterface, 2: CarbonInterface, 3: CarbonInterface}
     */
    private function resolvePeriod(Request $request): array
    {
        $mode = $request->string('mode')->toString() === 'range' ? 'range' : 'month';

        $monthStart = DashboardStats::resolveMonth($request->string('month')->toString());

        if ($mode === 'range') {
            $from = $request->filled('from')
                ? Carbon::parse($request->string('from')->toString())->startOfDay()
                : now()->subDays(29)->startOfDay();

            $to = $request->filled('to')
                ? Carbon::parse($request->string('to')->toString())->endOfDay()
                : now()->endOfDay();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
        } else {
            $from = $monthStart->copy()->startOfDay();
            $to = $monthStart->copy()->endOfMonth()->endOfDay();
        }

        return [$mode, $monthStart, $from, $to];
    }

    private function periodLabel(string $mode, CarbonInterface $monthStart, CarbonInterface $from, CarbonInterface $to): string
    {
        return $mode === 'month'
            ? DashboardStats::monthLabel($monthStart)
            : $from->format('d M Y').' – '.$to->format('d M Y');
    }
}
