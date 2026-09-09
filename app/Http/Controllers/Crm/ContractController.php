<?php

namespace App\Http\Controllers\Crm;

use App\Actions\Crm\ArchiveDocumentPdf;
use App\Actions\Crm\CreateInvoiceFromContract;
use App\Actions\Crm\RenderContractDocument;
use App\Actions\Crm\WriteContractClauses;
use App\Actions\Crm\WriteScopePoints;
use App\Concerns\SyncsLineItems;
use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use App\Enums\DocumentType;
use App\Exceptions\AiUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\BulkIdsRequest;
use App\Http\Requests\Crm\ContractDocumentRequest;
use App\Http\Requests\Crm\ContractRequest;
use App\Http\Requests\Crm\ContractSignRequest;
use App\Http\Requests\Crm\ScopePointsRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentSequence;
use App\Models\Lead;
use App\Models\ServicePackage;
use App\Support\Ai\Groq;
use App\Support\BulkDeleteSummary;
use App\Support\CompanyProfile;
use App\Support\Crm\ContractFormOptions;
use App\Support\Crm\ContractIndexQuery;
use App\Support\Csv\CsvExport;
use App\Support\Documents\HtmlSanitizer;
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

class ContractController extends Controller
{
    use SyncsLineItems;

    public function index(Request $request): Response
    {
        return Inertia::render('contracts/index', [
            ...ContractIndexQuery::build($request),
            'statuses' => EnumOptions::from(ContractStatus::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $lead = $request->filled('lead')
            ? Lead::query()->find($request->integer('lead'))
            : null;

        return Inertia::render('contracts/create', [
            ...ContractFormOptions::build(),
            'statuses' => EnumOptions::from(ContractStatus::class),
            'suggestedNumber' => DocumentSequence::preview(DocumentType::Contract),
            'lead' => $lead?->only(['id', 'company_name', 'estimated_value', 'converted_client_id']),
            'clientId' => $lead?->converted_client_id ?: ($request->integer('client') ?: null),
        ]);
    }

    public function show(Contract $contract): Response
    {
        $contract->load([
            'client', 'items.servicePackage:id,name', 'invoices', 'lead:id,company_name',
            'attachments.uploader:id,name',
        ]);

        return Inertia::render('contracts/show', [
            'contract' => $contract,
            'company' => CompanyProfile::identity(),
            'invoiceBlocker' => $contract->invoiceBlocker(),
            'types' => EnumOptions::from(ContractType::class),
            'statuses' => EnumOptions::from(ContractStatus::class),
            'billingCycles' => EnumOptions::from(BillingCycle::class),
            'signatureUrl' => $contract->signatureUrl(),
            ...$this->clausePanel($contract),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function clausePanel(Contract $contract): array
    {
        $written = $contract->aiClausePoints();
        $clauses = [];

        foreach (WriteContractClauses::clauseBlocks($contract) as $id => $block) {
            $clauses[] = [
                'id' => $id,
                'topic' => $block['topic'],
                'points' => $written[$id] ?? [],
            ];
        }

        return [
            'clauses' => $clauses,
            'aiClauses' => Groq::configured(),
            'documentEdited' => $contract->isDocumentEdited(),
        ];
    }

    public function edit(Contract $contract): Response
    {
        return Inertia::render('contracts/edit', [
            ...ContractFormOptions::build(),
            'statuses' => EnumOptions::from(ContractStatus::class),
            'contract' => $contract->load(['items.servicePackage:id,name']),
        ]);
    }

    public function nextNumber(Request $request): JsonResponse
    {
        return response()->json([
            'number' => DocumentSequence::preview(
                DocumentType::Contract,
                date: $request->filled('date') ? Carbon::parse($request->string('date')->toString()) : null,
            ),
        ]);
    }

    public function scopePoints(ScopePointsRequest $request, WriteScopePoints $writer): JsonResponse
    {
        $data = $request->validated();

        try {
            $points = $writer->handle(
                work: $data['name'],
                package: isset($data['service_package_id'])
                    ? ServicePackage::query()->with(['service', 'points'])->find((int) $data['service_package_id'])
                    : null,
                clientName: isset($data['client_id'])
                    ? Client::query()->find((int) $data['client_id'])?->company_name
                    : null,
                contractTitle: $data['title'] ?? null,
            );
        } catch (AiUnavailable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['points' => $points]);
    }

    public function document(Contract $contract, RenderContractDocument $renderer): Response
    {
        return Inertia::render('contracts/document', [
            'contract' => $contract->only(['id', 'number', 'title']),
            'page' => $renderer->editorPage($contract, $renderer->editable($contract)),
            'edited' => $contract->isDocumentEdited(),
        ]);
    }

    public function updateDocument(
        ContractDocumentRequest $request,
        Contract $contract,
        RenderContractDocument $renderer,
        ArchiveDocumentPdf $archiver,
    ): RedirectResponse {
        $contract->saveEditedBody(HtmlSanitizer::clean($request->validated()['body']));

        if ($contract->file_path !== null) {
            $archiver->forContract($contract, $renderer);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen disimpan.']);

        return back();
    }

    public function resetDocument(Contract $contract): RedirectResponse
    {
        $contract->forgetDocument();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Dokumen dikembalikan mengikuti template.',
        ]);

        return to_route('contracts.document', $contract);
    }

    public function clauses(Contract $contract, WriteContractClauses $writer): RedirectResponse
    {
        try {
            $writer->handle($contract->load(['items.servicePackage', 'client']));
        } catch (AiUnavailable $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pasal ditulis ulang. Periksa isinya sebelum dokumen difinalisasi.',
        ]);

        return back();
    }

    public function store(ContractRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $date = Carbon::parse($data['signed_date']);

        $number = $data['number'] ?? null;
        unset($data['number']);

        $contract = DB::transaction(function () use ($data, $items, $date, $number): Contract {
            $contract = Contract::create([
                ...$data,
                'number' => $number ?? DocumentSequence::next(DocumentType::Contract, date: $date),
                'created_by' => auth()->id(),
            ]);

            $this->syncItems($contract, $items);
            $contract->recalculate();

            if ($number !== null) {
                DocumentSequence::claim(DocumentType::Contract, $number, date: $date);
            }

            return $contract;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "MoU {$contract->number} dibuat."]);

        return to_route('contracts.show', $contract);
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use ($contract, $data, $items): void {
            $contract->update($data);
            $contract->items()->delete();
            $this->syncItems($contract, $items);
            $contract->recalculate();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'MoU diperbarui.']);

        return to_route('contracts.show', $contract);
    }

    public function sign(
        ContractSignRequest $request,
        Contract $contract,
        RenderContractDocument $renderer,
        ArchiveDocumentPdf $archiver,
    ): RedirectResponse {
        if (! $contract->status->canBeSigned()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'MoU ini sudah lewat tahap tanda tangan.',
            ]);

            return back();
        }

        $data = $request->validated();
        $file = $request->file('signature');

        $contract->update([
            'status' => ContractStatus::Signed,
            'signed_date' => $data['signed_date'] ?? $contract->signed_date?->toDateString() ?? now()->toDateString(),
            ...($file === null ? [] : ['signature_path' => $contract->replaceSignature($file)]),
        ]);

        if ($contract->file_path !== null) {
            $renderer->handle($contract);
            $archiver->forContract($contract, $renderer);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'MoU ditandai sudah ditandatangani.']);

        return back();
    }

    public function signature(Contract $contract): StreamedResponse
    {
        $disk = Storage::disk(Contract::SIGNATURE_DISK);

        abort_if($contract->signature_path === null || ! $disk->exists($contract->signature_path), 404);

        return $disk->response($contract->signature_path, headers: [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    public function finalize(
        Contract $contract,
        RenderContractDocument $renderer,
        ArchiveDocumentPdf $archiver,
        WriteContractClauses $writer,
    ): RedirectResponse {
        $warning = $this->writePendingClauses($contract, $writer);

        $renderer->handle($contract);
        $archiver->forContract($contract, $renderer);

        Inertia::flash('toast', $warning === null
            ? ['type' => 'success', 'message' => 'Dokumen MoU digenerate dan PDF diarsipkan.']
            : ['type' => 'error', 'message' => $warning]);

        return back();
    }

    private function writePendingClauses(Contract $contract, WriteContractClauses $writer): ?string
    {
        $pending = WriteContractClauses::clauseBlocks($contract) !== []
            && empty($contract->ai_clauses);

        if (! $pending || ! Groq::configured()) {
            return null;
        }

        try {
            $writer->handle($contract->load(['items.servicePackage', 'client']));
        } catch (AiUnavailable $e) {
            return 'PDF diarsipkan, tapi pasal AI gagal ditulis dan memakai teks cadangan. '.$e->getMessage();
        }

        return null;
    }

    public function pdf(
        Contract $contract,
        RenderContractDocument $renderer,
        ArchiveDocumentPdf $archiver,
    ): StreamedResponse {
        $path = $contract->file_path;

        if ($path === null || ! Storage::disk(ArchiveDocumentPdf::DISK)->exists($path)) {
            $path = $archiver->forContract($contract, $renderer);
        }

        return Storage::disk(ArchiveDocumentPdf::DISK)->download(
            $path,
            DownloadName::safe($contract->number).'.pdf',
        );
    }

    public function invoice(Contract $contract, CreateInvoiceFromContract $creator): RedirectResponse
    {
        $blocker = $contract->invoiceBlocker();

        if ($blocker !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $blocker]);

            return back();
        }

        $invoice = $creator->handle($contract);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Invoice {$invoice->number} dibuat."]);

        return to_route('invoices.show', $invoice);
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'MoU dihapus.']);

        return ListRedirect::to('contracts.index');
    }

    public function destroyBulk(BulkIdsRequest $request): RedirectResponse
    {
        $contracts = Contract::query()->whereIn('id', $request->ids())->get();

        foreach ($contracts as $contract) {
            $contract->delete();
        }

        Inertia::flash('toast', BulkDeleteSummary::toast($contracts->count(), 0, 'MoU', ''));

        return ListRedirect::to('contracts.index');
    }

    public function exportCsv(BulkIdsRequest $request): StreamedResponse
    {
        $contracts = Contract::query()
            ->whereIn('id', $request->ids())
            ->select(['id', 'client_id', 'number', 'title', 'start_date', 'end_date', 'value', 'status'])
            ->with('client:id,company_name')
            ->orderBy('number')
            ->get();

        return CsvExport::download(
            'mou-'.now()->format('Ymd-His').'.csv',
            ['Nomor', 'Judul', 'Klien', 'Mulai', 'Selesai', 'Nilai', 'Status'],
            $contracts->map(fn (Contract $contract): array => [
                $contract->number,
                $contract->title,
                $contract->client->company_name ?? 'Tanpa klien',
                $contract->start_date?->format('Y-m-d') ?? '-',
                $contract->end_date?->format('Y-m-d') ?? '-',
                (string) $contract->value,
                $contract->status->label(),
            ]),
        );
    }
}
