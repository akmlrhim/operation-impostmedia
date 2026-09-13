<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\AttachmentRequest;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Support\DownloadName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public const DISK = 'local';

    /**
     * @var array<string, class-string<Lead|Client|Contract|Invoice>>
     */
    private const OWNERS = [
        'leads' => Lead::class,
        'clients' => Client::class,
        'contracts' => Contract::class,
        'invoices' => Invoice::class,
    ];

    public function store(AttachmentRequest $request, string $type, int $id): RedirectResponse
    {
        $owner = $this->owner($type, $id);
        $this->ensureVisible($owner);
        $file = $request->file('file');

        $owner->attachments()->create([
            'name' => $request->string('name')->toString() ?: $file->getClientOriginalName(),
            'path' => $file->store('attachments/'.$type, self::DISK),
            'disk' => self::DISK,
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lampiran diunggah.']);

        return back();
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->ensureOwnerAccessible($attachment);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            DownloadName::safe($attachment->name, 'lampiran'),
        );
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $this->ensureOwnerAccessible($attachment);

        if (Storage::disk($attachment->disk)->exists($attachment->path)) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $attachment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lampiran dihapus.']);

        return back();
    }

    private function owner(string $type, int $id): Lead|Client|Contract|Invoice
    {
        abort_unless(array_key_exists($type, self::OWNERS), 404);

        return self::OWNERS[$type]::query()->findOrFail($id);
    }

    private function ensureOwnerAccessible(Attachment $attachment): void
    {
        $owner = $attachment->attachable;

        abort_if($owner === null, 404);

        if ($owner instanceof Payment) {
            $this->ensureVisible($owner->invoice);

            return;
        }

        if ($owner instanceof Lead || $owner instanceof Client || $owner instanceof Contract || $owner instanceof Invoice) {
            $this->ensureVisible($owner);
        }
    }
}
