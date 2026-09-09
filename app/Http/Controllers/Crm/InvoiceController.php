<?php

namespace App\Http\Controllers\Crm;

use App\Actions\Crm\ArchiveDocumentPdf;
use App\Actions\Crm\CreateInvoice;
use App\Actions\Crm\SyncInvoiceStatus;
use App\Concerns\SyncsLineItems;
use App\Enums\DocumentType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\BulkIdsRequest;
use App\Http\Requests\Crm\InvoiceRequest;
use App\Models\Client;
use App\Models\DocumentSequence;
use App\Models\Invoice;
use App\Support\BulkDeleteSummary;
use App\Support\Crm\InvoiceFormOptions;
use App\Support\Crm\InvoiceIndexQuery;
use App\Support\Csv\CsvExport;
use App\Support\DownloadName;
use App\Support\EnumOptions;
use App\Support\ListRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use SyncsLineItems;

    public function index(Request $request): Response
    {
        return Inertia::render('invoices/index', [
            ...InvoiceIndexQuery::build($request),
            'statuses' => EnumOptions::from(InvoiceStatus::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $client = Client::find($request->integer('client'));

        return Inertia::render('invoices/create', [
            ...InvoiceFormOptions::build(),
            'statuses' => EnumOptions::from(InvoiceStatus::class),
            'suggestedNumber' => DocumentSequence::preview(DocumentType::Invoice, client: $client),
            'clientId' => $client?->id,
        ]);
    }

    public function nextNumber(Request $request): JsonResponse
    {
        return response()->json([
            'number' => DocumentSequence::preview(
                DocumentType::Invoice,
                date: $request->filled('date') ? Carbon::parse($request->string('date')->toString()) : null,
                client: Client::find($request->integer('client')),
            ),
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('invoices/show', [
            'invoice' => $invoice->load([
                'client',
                'contract:id,number,title',
                'items.servicePackage:id,name',
                'payments.recorder:id,name',
                'attachments.uploader:id,name',
            ]),
            'statuses' => EnumOptions::from(InvoiceStatus::class),
            'methods' => EnumOptions::from(PaymentMethod::class),
        ]);
    }

    public function edit(Invoice $invoice): RedirectResponse|Response
    {
        if (! $invoice->status->isEditable()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Invoice yang sudah dikirim tidak bisa diubah. Batalkan dulu bila perlu koreksi.',
            ]);

            return to_route('invoices.show', $invoice);
        }

        return Inertia::render('invoices/edit', [
            ...InvoiceFormOptions::build(),
            'statuses' => EnumOptions::from(InvoiceStatus::class),
            'invoice' => $invoice->load(['items.servicePackage:id,name']),
        ]);
    }

    public function store(InvoiceRequest $request, CreateInvoice $creator): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $number = $data['number'] ?? null;
        unset($data['number']);

        $invoice = $creator->handle($data, $items, $number);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Invoice {$invoice->number} dibuat."]);

        return to_route('invoices.show', $invoice);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if (! $invoice->status->isEditable()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Invoice yang sudah dikirim tidak bisa diubah. Batalkan dulu bila perlu koreksi.',
            ]);

            return back();
        }

        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use ($invoice, $data, $items): void {
            $invoice->update($data);
            $invoice->items()->delete();
            $this->syncItems($invoice, $items);
            $invoice->recalculate();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice diperbarui.']);

        return to_route('invoices.show', $invoice);
    }

    public function send(
        Invoice $invoice,
        SyncInvoiceStatus $sync,
        ArchiveDocumentPdf $archiver,
    ): RedirectResponse {
        $invoice->update(['status' => InvoiceStatus::Sent]);
        $sync->handle($invoice);

        $archiver->forInvoice($invoice->refresh());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice ditandai terkirim dan PDF diarsipkan.']);

        return back();
    }

    public function pdf(Invoice $invoice, ArchiveDocumentPdf $archiver): StreamedResponse
    {
        $path = $archiver->forInvoice($invoice);

        return Storage::disk(ArchiveDocumentPdf::DISK)->download(
            $path,
            DownloadName::safe($invoice->number).'.pdf',
        );
    }

    public function settle(Invoice $invoice, SyncInvoiceStatus $sync): RedirectResponse
    {
        if (! $invoice->status->isOutstanding() || (float) $invoice->balance_due <= 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Hanya invoice terkirim yang masih punya sisa tagihan bisa dilunaskan.',
            ]);

            return back();
        }

        $invoice->payments()->create([
            'amount' => $invoice->balance_due,
            'paid_at' => now()->toDateString(),
            'method' => PaymentMethod::Transfer,
            'recorded_by' => auth()->id(),
        ]);

        $sync->handle($invoice);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice ditandai lunas.']);

        return back();
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['status' => InvoiceStatus::Void]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice dibatalkan.']);

        return back();
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice dihapus.']);

        return ListRedirect::to('invoices.index');
    }

    public function destroyBulk(BulkIdsRequest $request): RedirectResponse
    {
        $invoices = Invoice::query()->whereIn('id', $request->ids())->get();

        foreach ($invoices as $invoice) {
            $invoice->delete();
        }

        Inertia::flash('toast', BulkDeleteSummary::toast($invoices->count(), 0, 'invoice', ''));

        return ListRedirect::to('invoices.index');
    }

    public function exportCsv(BulkIdsRequest $request): StreamedResponse
    {
        $invoices = Invoice::query()
            ->whereIn('id', $request->ids())
            ->with('client:id,company_name')
            ->orderBy('number')
            ->get();

        return CsvExport::download(
            'invoice-'.now()->format('Ymd-His').'.csv',
            ['Nomor', 'Klien', 'Terbit', 'Jatuh Tempo', 'Total', 'Sisa', 'Status'],
            $invoices->map(fn (Invoice $invoice): array => [
                $invoice->number,
                $invoice->client->company_name ?? 'Tanpa klien',
                $invoice->issue_date->format('Y-m-d'),
                $invoice->due_date->format('Y-m-d'),
                (string) $invoice->total,
                (string) $invoice->balance_due,
                $invoice->status->label(),
            ]),
        );
    }
}
