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

class ServiceCustomPriceTest extends TestCase
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

    public function test_a_package_price_can_be_custom_without_a_fixed_value(): void
    {
        $this->post(route('services.store'), [
            'type' => 'umkm',
            'name' => 'Konsultasi Strategi',
            'is_active' => true,
            'packages' => [
                [
                    'name' => 'Sesi Konsultasi',
                    'price' => null,
                    'unit' => 'sesi',
                    'billing_type' => 'per_project',
                    'is_active' => true,
                    'points' => [['label' => '1x Konsultasi']],
                ],
                [
                    'name' => 'Paket Legal',
                    'price' => 5_000_000,
                    'unit' => 'paket',
                    'billing_type' => 'per_project',
                    'is_active' => true,
                    'points' => [],
                ],
            ],
        ])->assertRedirect();

        $service = Service::where('name', 'Konsultasi Strategi')->firstOrFail();
        $custom = $service->packages()->where('name', 'Sesi Konsultasi')->firstOrFail();
        $fixed = $service->packages()->where('name', 'Paket Legal')->firstOrFail();

        $this->assertNull($custom->price);
        $this->assertSame('5000000.00', $fixed->price);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('services/index')
                ->where('services', function ($services): bool {
                    $found = $services->firstWhere('name', 'Konsultasi Strategi');
                    $package = collect($found['packages'])->firstWhere('name', 'Sesi Konsultasi');

                    return $package['price'] === null;
                }));
    }

    public function test_a_custom_price_package_requires_a_filled_amount_when_used_in_a_contract(): void
    {
        $package = $this->makeCustomPackage('Konsultasi Strategi');

        $base = [
            'client_id' => $this->makeClient()->id,
            'type' => 'mou',
            'title' => 'Pendampingan Strategi Bisnis',
            'tax_percent' => 11,
            'billing_cycle' => 'one_time',
            'signed_date' => '2026-09-01',
            'status' => 'draft',
        ];

        $this->post(route('contracts.store'), [...$base, 'items' => [[
            'service_package_id' => $package->id,
            'name' => $package->name,
            'quantity' => 1,
            'unit' => 'sesi',
            'unit_price' => null,
        ]]])->assertSessionHasErrors('items.0.unit_price');

        $this->post(route('contracts.store'), [...$base, 'items' => [[
            'service_package_id' => $package->id,
            'name' => $package->name,
            'quantity' => 1,
            'unit' => 'sesi',
            'unit_price' => 7_500_000,
        ]]])->assertRedirect();

        $contract = Contract::firstOrFail();

        $this->assertSame('7500000.00', $contract->subtotal);
        $this->assertSame('8325000.00', $contract->value);
    }

    public function test_the_filled_custom_amount_flows_into_the_invoice_created_from_a_contract(): void
    {
        $package = $this->makeCustomPackage('Konsultasi Strategi');
        $client = $this->makeClient();

        $this->post(route('contracts.store'), [
            'client_id' => $client->id,
            'type' => 'mou',
            'title' => 'Pendampingan Strategi Bisnis',
            'tax_percent' => 11,
            'billing_cycle' => 'monthly',
            'signed_date' => '2026-09-01',
            'status' => 'signed',
            'items' => [[
                'service_package_id' => $package->id,
                'name' => $package->name,
                'quantity' => 1,
                'unit' => 'sesi',
                'unit_price' => 9_000_000,
            ]],
        ])->assertRedirect();

        $contract = Contract::firstOrFail();

        $this->post(route('contracts.invoice', $contract))->assertRedirect();

        $invoice = $contract->invoices()->firstOrFail();
        $this->assertSame('9000000.00', $invoice->items()->firstOrFail()->unit_price);
        $this->assertSame('9000000.00', $invoice->items()->firstOrFail()->amount);
    }

    private function makeCustomPackage(string $serviceName): ServicePackage
    {
        $this->post(route('services.store'), [
            'type' => 'umkm',
            'name' => $serviceName,
            'is_active' => true,
            'packages' => [[
                'name' => 'Sesi Konsultasi',
                'price' => null,
                'unit' => 'sesi',
                'billing_type' => 'per_project',
                'is_active' => true,
                'points' => [],
            ]],
        ])->assertRedirect();

        $service = Service::where('name', $serviceName)->firstOrFail();

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
