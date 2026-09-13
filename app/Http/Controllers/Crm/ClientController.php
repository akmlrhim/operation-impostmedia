<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ClientStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\BulkIdsRequest;
use App\Http\Requests\Crm\ClientRequest;
use App\Models\Client;
use App\Support\BulkDeleteSummary;
use App\Support\Crm\ClientIndexQuery;
use App\Support\Crm\UserOptions;
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
            'clients' => ClientIndexQuery::build($filterClientId, $status, $sort, $direction, $request->user()),
            'filters' => [
                'status' => $status,
                'filterClient' => $filterClientId,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'statuses' => EnumOptions::from(ClientStatus::class),
            'users' => UserOptions::assignable(),
            'filterClients' => Client::query()
                ->visibleTo($request->user())
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
        $this->ensureVisible($client);

        $user = auth()->user();

        return Inertia::render('clients/show', [
            'client' => $client->load(['attachments.uploader:id,name', 'assignees:id,name']),
            'contracts' => $client->contracts()
                ->visibleTo($user)
                ->select(['id', 'client_id', 'number', 'title', 'value', 'status', 'start_date', 'end_date'])
                ->latest('id')
                ->get(),
            'invoices' => $client->invoices()
                ->visibleTo($user)
                ->latest('id')
                ->get(),
            'statuses' => EnumOptions::from(ClientStatus::class),
            'users' => UserOptions::assignable(),
        ]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $assignedToIds = $data['assigned_to_ids'] ?? [];
        unset($data['assigned_to_ids']);

        $client = Client::create([
            ...$data,
            'short_code' => $data['short_code'] ?? Client::generateShortCode($data['company_name']),
            'created_by' => auth()->id(),
        ]);

        $client->assignees()->sync($assignedToIds);
        $client->notifyNewAssignees();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Klien {$client->company_name} ditambahkan."]);

        return to_route('clients.show', $client);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->ensureVisible($client);

        $data = $request->validated();
        $assignedToIds = $data['assigned_to_ids'] ?? [];
        unset($data['assigned_to_ids']);

        $data['short_code'] ??= Client::generateShortCode($data['company_name'], $client->id);

        $client->update($data);
        $client->assignees()->sync($assignedToIds);
        $client->notifyNewAssignees();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Data klien diperbarui.']);

        return to_route('clients.show', $client);
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klien dihapus.']);

        return ListRedirect::to('clients.index');
    }

    public function destroyBulk(BulkIdsRequest $request): RedirectResponse
    {
        $clients = Client::query()->whereIn('id', $request->ids())->get();

        foreach ($clients as $client) {
            $client->delete();
        }

        Inertia::flash('toast', BulkDeleteSummary::toast($clients->count(), 0, 'klien', ''));

        return ListRedirect::to('clients.index');
    }

    public function exportCsv(BulkIdsRequest $request): StreamedResponse
    {
        $clients = Client::query()
            ->whereIn('id', $request->ids())
            ->withCount(['contracts', 'invoices'])
            ->orderBy('company_name')
            ->get();

        return CsvExport::download(
            'klien-'.now()->format('Ymd-His').'.csv',
            ['Perusahaan', 'PIC', 'Jabatan PIC', 'Kota', 'Status', 'Jumlah MoU', 'Jumlah Invoice'],
            $clients->map(fn (Client $client): array => [
                $client->company_name,
                $client->contact_name ?? '-',
                $client->contact_position ?? '-',
                $client->city ?? '-',
                $client->status->label(),
                (string) $client->contracts_count,
                (string) $client->invoices_count,
            ]),
        );
    }
}
