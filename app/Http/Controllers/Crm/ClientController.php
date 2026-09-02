<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ClientStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\BulkIdsRequest;
use App\Http\Requests\Crm\ClientRequest;
use App\Models\Client;
use App\Models\User;
use App\Support\BulkDeleteSummary;
use App\Support\Crm\ClientIndexQuery;
use App\Support\Csv\CsvExport;
use App\Support\EnumOptions;
use App\Support\ListRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $status = ClientStatus::tryFrom($request->string('status')->toString())->value ?? '';
        $filterClientId = $request->integer('filter_client') ?: null;

        if ($filterClientId !== null && ! Client::whereKey($filterClientId)->exists()) {
            $filterClientId = null;
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        return Inertia::render('clients/index', [
            'clients' => ClientIndexQuery::build($filterClientId, $status, $sort, $direction),
            'filters' => [
                'status' => $status,
                'filterClient' => $filterClientId,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'statuses' => EnumOptions::from(ClientStatus::class),
            'users' => User::query()->where('is_active', true)->get(['id', 'name']),
            'filterClients' => Client::query()
                ->orderBy('company_name')
                ->get(['id', 'company_name']),
        ]);
    }

    public function shortCode(Request $request): JsonResponse
    {
        $companyName = trim($request->string('company_name')->toString());

        return response()->json([
            'short_code' => $companyName === ''
                ? ''
                : Client::generateShortCode($companyName, $request->integer('client') ?: null),
        ]);
    }

    public function show(Client $client): Response
    {
        return Inertia::render('clients/show', [
            'client' => $client->load(['accountManager:id,name', 'attachments.uploader:id,name']),
            'contracts' => $client->contracts()->latest('id')->get(),
            'invoices' => $client->invoices()->latest('id')->get(),
            'statuses' => EnumOptions::from(ClientStatus::class),
            'users' => User::query()->where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $client = Client::create([
            ...$data,
            'short_code' => $data['short_code'] ?? Client::generateShortCode($data['company_name']),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Klien {$client->company_name} ditambahkan."]);

        return to_route('clients.show', $client);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $data = $request->validated();

        $data['short_code'] ??= Client::generateShortCode($data['company_name'], $client->id);

        $client->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Data klien diperbarui.']);

        return to_route('clients.show', $client);
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->invoices()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Klien tidak bisa dihapus karena sudah punya invoice.',
            ]);

            return back();
        }

        $client->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klien dihapus.']);

        return ListRedirect::to('clients.index');
    }

    public function destroyBulk(BulkIdsRequest $request): RedirectResponse
    {
        $clients = Client::query()->whereIn('id', $request->ids())->withCount('invoices')->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($clients as $client) {
            if ($client->invoices_count > 0) {
                $skipped++;

                continue;
            }

            $client->delete();
            $deleted++;
        }

        Inertia::flash('toast', BulkDeleteSummary::toast($deleted, $skipped, 'klien', 'sudah punya invoice'));

        return ListRedirect::to('clients.index');
    }

    public function exportCsv(BulkIdsRequest $request): StreamedResponse
    {
        $clients = Client::query()
            ->whereIn('id', $request->ids())
            ->withCount(['contracts', 'invoices'])
            ->with('accountManager:id,name')
            ->orderBy('company_name')
            ->get();

        return CsvExport::download(
            'klien-'.now()->format('Ymd-His').'.csv',
            ['Perusahaan', 'PIC', 'Jabatan PIC', 'Kota', 'Account Manager', 'Status', 'Jumlah MoU', 'Jumlah Invoice'],
            $clients->map(fn (Client $client): array => [
                $client->company_name,
                $client->contact_name ?? '-',
                $client->contact_position ?? '-',
                $client->city ?? '-',
                $client->account_manager_id === null ? '-' : $client->accountManager->name,
                $client->status->label(),
                (string) $client->contracts_count,
                (string) $client->invoices_count,
            ]),
        );
    }
}
