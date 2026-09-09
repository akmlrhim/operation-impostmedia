<?php

namespace App\Actions\Crm;

use App\Models\CompanySetting;
use App\Models\Contract;
use App\Models\Invoice;
use App\Support\CompanyProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchiveDocumentPdf
{
    public const DISK = 'local';

    public function forContract(Contract $contract, RenderContractDocument $renderer): string
    {
        $body = $contract->renderedBody() ?: $renderer->handle($contract, persist: false);

        return $this->store($contract, 'mou', $contract->number, view('documents.print', [
            'title' => $contract->number,
            'body' => $body,
        ])->render());
    }

    public function forInvoice(Invoice $invoice): string
    {
        return $this->store($invoice, 'invoice', $invoice->number, view('documents.invoice', [
            'invoice' => $invoice->load(['items', 'client', 'payments']),
            'company' => CompanySetting::current(),
            'profile' => CompanyProfile::all(),
        ])->render());
    }

    private function store(Model $document, string $folder, string $number, string $html): string
    {
        $path = $folder.'/'.Str::slug($number).'.pdf';
        $previous = $document->getAttribute('file_path');

        if ($previous !== null && $previous !== $path && Storage::disk(self::DISK)->exists($previous)) {
            Storage::disk(self::DISK)->delete($previous);
        }

        Storage::disk(self::DISK)->put(
            $path,
            Pdf::loadHTML($html)->setPaper('a4')->output(),
        );

        $document->forceFill(['file_path' => $path])->save();

        return $path;
    }
}
