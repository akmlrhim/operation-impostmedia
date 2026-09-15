<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ServiceBillingType;
use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ServiceRequest;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Support\EnumOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('services/index', [
            'services' => Service::query()
                ->with('packages.points')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(),
            'types' => EnumOptions::from(ServiceType::class),
            'billingTypes' => EnumOptions::from(ServiceBillingType::class),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $packages = $data['packages'];
        unset($data['packages']);

        DB::transaction(function () use ($data, $packages): void {
            $this->syncPackages(Service::create($data), $packages);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Layanan ditambahkan.']);

        return to_route('services.index');
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();
        $packages = $data['packages'];
        unset($data['packages']);

        DB::transaction(function () use ($service, $data, $packages): void {
            $service->update($data);
            $this->syncPackages($service, $packages);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Layanan diperbarui.']);

        return to_route('services.index');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Layanan dihapus.']);

        return back();
    }

    /**
     * @param  array<int, array<string, mixed>>  $packages
     */
    private function syncPackages(Service $service, array $packages): void
    {
        $existing = $service->packages()->get()->keyBy('id');
        $kept = [];

        foreach ($packages as $position => $package) {
            $points = $package['points'] ?? [];
            $id = $package['id'] ?? null;
            unset($package['points'], $package['id']);
            $package['quantity'] = $package['quantity'] ?? 1;

            $model = $existing->get($id) ?? $service->packages()->make();
            $model->fill([...$package, 'position' => $position])->save();

            $kept[] = $model->id;

            $model->points()->delete();

            foreach ($points as $pointPosition => $point) {
                $model->points()->create([
                    'label' => $point['label'],
                    'position' => $pointPosition,
                ]);
            }
        }

        $this->pruneRemovedPackages($service, $kept);
    }

    /**
     * @param  array<int, int>  $kept
     */
    private function pruneRemovedPackages(Service $service, array $kept): void
    {
        $removed = $service->packages()->whereNotIn('id', $kept)->get();

        foreach ($removed as $package) {
            $this->removePackage($package);
        }
    }

    private function removePackage(ServicePackage $package): void
    {
        if ($package->contractItems()->exists() || $package->invoiceItems()->exists()) {
            $package->update(['is_active' => false]);

            return;
        }

        $package->delete();
    }
}
