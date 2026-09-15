<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServicePackageQuantityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_a_package_quantity_is_saved_and_returned_as_a_label(): void
    {
        $this->post(route('services.store'), [
            'type' => 'umkm',
            'name' => 'Live Streaming',
            'is_active' => true,
            'packages' => [[
                'name' => 'Siaran Langsung',
                'price' => 9_000_000,
                'quantity' => 12,
                'unit' => 'hari',
                'billing_type' => 'per_project',
                'is_active' => true,
                'points' => [],
            ]],
        ])->assertRedirect();

        $service = Service::where('name', 'Live Streaming')->firstOrFail();
        $package = $service->packages()->firstOrFail();

        $this->assertSame(12, $package->quantity);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('services/index')
                ->where('services', function ($services): bool {
                    $found = $services->firstWhere('name', 'Live Streaming');
                    $package = collect($found['packages'])->firstWhere('name', 'Siaran Langsung');

                    return (int) $package['quantity'] === 12;
                }));
    }

    public function test_a_package_quantity_defaults_to_one_when_not_sent(): void
    {
        $this->post(route('services.store'), [
            'type' => 'umkm',
            'name' => 'Pendampingan Dasar',
            'is_active' => true,
            'packages' => [[
                'name' => 'Paket Konsultasi',
                'price' => 500_000,
                'unit' => 'sesi',
                'billing_type' => 'per_project',
                'is_active' => true,
                'points' => [],
            ]],
        ])->assertRedirect();

        $package = Service::where('name', 'Pendampingan Dasar')->firstOrFail()->packages()->firstOrFail();

        $this->assertSame(1, $package->quantity);
    }

    public function test_the_labelled_quantity_does_not_multiply_the_contract_line_amount(): void
    {
        $package = $this->makeTimedPackage();

        $this->post(route('contracts.store'), [
            'client_id' => $this->makeClient()->id,
            'type' => 'mou',
            'title' => 'Siaran Langsung Event Tahunan',
            'tax_percent' => 11,
            'billing_cycle' => 'one_time',
            'signed_date' => '2026-09-01',
            'status' => 'draft',
            'items' => [[
                'service_package_id' => $package->id,
                'name' => 'Live Streaming - Siaran Langsung',
                'quantity' => 1,
                'unit' => 'hari',
                'unit_price' => 9_000_000,
            ]],
        ])->assertRedirect();

        $contract = Contract::firstOrFail();

        $this->assertSame('9000000.00', $contract->subtotal);
        $this->assertSame('9990000.00', $contract->value);
    }

    private function makeTimedPackage(): ServicePackage
    {
        $this->post(route('services.store'), [
            'type' => 'umkm',
            'name' => 'Live Streaming',
            'is_active' => true,
            'packages' => [[
                'name' => 'Siaran Langsung',
                'price' => 9_000_000,
                'quantity' => 12,
                'unit' => 'hari',
                'billing_type' => 'per_project',
                'is_active' => true,
                'points' => [],
            ]],
        ])->assertRedirect();

        $service = Service::where('name', 'Live Streaming')->firstOrFail();

        return $service->packages()->firstOrFail();
    }

    private function makeClient(): Client
    {
        return Client::create([
            'company_name' => 'PT Kopi Nusantara',
            'address' => 'Jl. Sudirman No. 1',
            'city' => 'Jakarta',
            'contact_name' => 'Dewi Lestari',
            'contact_position' => 'Marketing Manager',
        ]);
    }
}
