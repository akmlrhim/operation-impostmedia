<?php

namespace Tests\Feature\Crm;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServicesOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_are_listed_oldest_first_on_the_services_page(): void
    {
        $oldest = Service::create(['type' => 'umkm', 'name' => 'Layanan Paling Lama']);
        $oldest->update(['created_at' => now()->subDays(10)]);

        $middle = Service::create(['type' => 'umkm', 'name' => 'Layanan Kedua']);
        $middle->update(['created_at' => now()->subDays(5)]);

        $newest = Service::create(['type' => 'brand', 'name' => 'Layanan Terbaru']);

        $this->actingAs(User::factory()->create())
            ->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('services/index')
                ->where('services', fn ($services): bool => collect($services)->pluck('id')->all() === [
                    $oldest->id,
                    $middle->id,
                    $newest->id,
                ]));
    }

    public function test_pickable_services_are_ordered_oldest_first(): void
    {
        $oldest = Service::create(['type' => 'umkm', 'name' => 'Layanan Paling Lama']);
        $oldest->update(['created_at' => now()->subDays(20)]);
        $oldest->packages()->create([
            'name' => 'Paket A',
            'price' => 100_000,
            'unit' => 'paket',
            'billing_type' => 'one_time',
        ]);

        $newest = Service::create(['type' => 'brand', 'name' => 'Layanan Terbaru']);
        $newest->packages()->create([
            'name' => 'Paket B',
            'price' => 200_000,
            'unit' => 'paket',
            'billing_type' => 'one_time',
        ]);

        $this->assertSame([$oldest->id, $newest->id], Service::pickable()->pluck('id')->all());
    }
}
