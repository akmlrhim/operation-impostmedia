<?php

namespace Tests\Feature\Crm;

use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\User;
use App\Support\Crm\DashboardStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTemperatureTest extends TestCase
{
    use RefreshDatabase;

    private LeadStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $this->stage = LeadStage::create([
            'name' => 'Prospek', 'slug' => 'prospek', 'color' => '#64748b', 'position' => 0, 'type' => 'open',
        ]);
    }

    public function test_dashboard_groups_open_leads_by_temperature(): void
    {
        $this->makeLead('PT Panas', 'hot', 100_000_000);
        $this->makeLead('PT Panas Dua', 'hot', 50_000_000);
        $this->makeLead('PT Hangat', 'warm', 20_000_000);
        $this->makeLead('PT Dingin', 'cold', 5_000_000);

        [$hot, $warm, $cold] = DashboardStats::temperature();

        $this->assertSame(['hot', 'Hot', 2, 150_000_000.0], [
            $hot['value'], $hot['label'], $hot['count'], $hot['total'],
        ]);
        $this->assertSame([1, 20_000_000.0], [$warm['count'], $warm['total']]);
        $this->assertSame([1, 5_000_000.0], [$cold['count'], $cold['total']]);
    }

    public function test_closed_leads_are_left_out_of_the_temperature_breakdown(): void
    {
        $this->makeLead('PT Deal', 'hot', 90_000_000, 'won');
        $this->makeLead('PT Gagal', 'hot', 90_000_000, 'lost');
        $this->makeLead('PT Jalan', 'hot', 10_000_000);

        [$hot] = DashboardStats::temperature();

        $this->assertSame(1, $hot['count']);
        $this->assertSame(10_000_000.0, $hot['total']);
    }

    public function test_breakdown_always_lists_the_three_levels_hottest_first(): void
    {
        $buckets = DashboardStats::temperature();

        $this->assertSame(['hot', 'warm', 'cold'], array_column($buckets, 'value'));

        foreach ($buckets as $bucket) {
            $this->assertSame(0, $bucket['count']);
            $this->assertSame(0.0, $bucket['total']);
            $this->assertNotSame('', $bucket['description']);
        }
    }

    public function test_leads_table_can_be_sorted_by_temperature(): void
    {
        $this->makeLead('PT Panas', 'hot', 0);
        $this->makeLead('PT Dingin', 'cold', 0);

        $this->get(route('leads.index', ['tab' => 'table', 'sort' => 'temperature', 'direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stageTables.0.leads.0.company_name', 'PT Dingin')
                ->where('stageTables.0.leads.1.company_name', 'PT Panas')
                ->etc());
    }

    public function test_lead_pages_offer_the_temperature_options(): void
    {
        $lead = $this->makeLead('PT Panas', 'hot', 0);

        foreach ([route('leads.index'), route('leads.show', $lead)] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('temperatures.0.value', 'cold')
                    ->where('temperatures.2.label', 'Hot')
                    ->etc());
        }
    }

    private function makeLead(string $name, string $temperature, int $value, string $status = 'open'): Lead
    {
        return Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => $name,
            'contact_name' => 'PIC '.$name,
            'estimated_value' => $value,
            'temperature' => $temperature,
            'status' => $status,
        ]);
    }
}
