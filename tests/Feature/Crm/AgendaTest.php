<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LeadStage $stage;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->stage = LeadStage::create([
            'name' => 'Prospek', 'slug' => 'prospek', 'color' => '#64748b', 'position' => 0, 'type' => 'open',
        ]);

        $this->client = Client::create(['company_name' => 'PT Kalender', 'short_code' => 'KAL']);
    }

    public function test_the_month_carries_follow_ups_due_dates_and_contract_dates(): void
    {
        Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Tindak Lanjut',
            'contact_name' => 'Dewi',
            'next_action' => 'Kirim penawaran',
            'next_action_date' => '2026-09-10',
        ]);

        Invoice::create([
            'number' => 'IM-INV-0001',
            'client_id' => $this->client->id,
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-15',
            'total' => 1000,
            'balance_due' => 1000,
            'status' => 'sent',
        ]);

        Contract::create([
            'number' => 'IM-MOU-0001',
            'client_id' => $this->client->id,
            'title' => 'Kerja sama',
            'signed_date' => '2026-09-01',
            'start_date' => '2026-09-05',
            'end_date' => '2026-09-25',
        ]);

        $this->get(route('dashboard', ['month' => '2026-09']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('general')
                ->where('month', '2026-09')
                ->has('events', 4)
                ->where('events.0.kind', 'contract-start')
                ->where('events.1.kind', 'lead')
                ->where('events.2.kind', 'invoice')
                ->where('events.3.kind', 'contract-end'));
    }

    public function test_the_type_filter_keeps_only_what_is_asked_for(): void
    {
        Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Tindak Lanjut',
            'contact_name' => 'Dewi',
            'next_action_date' => '2026-09-10',
        ]);

        Invoice::create([
            'number' => 'IM-INV-0002',
            'client_id' => $this->client->id,
            'issue_date' => '2026-09-01',
            'due_date' => '2026-09-15',
            'total' => 1000,
            'balance_due' => 1000,
            'status' => 'sent',
        ]);

        $this->get(route('dashboard', ['month' => '2026-09', 'types' => 'lead']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('events', 1)
                ->where('events.0.kind', 'lead'));
    }

    public function test_mine_only_keeps_records_the_signed_in_user_is_involved_with(): void
    {
        $other = User::factory()->create();

        $ownLead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'Lead orang lain',
            'contact_name' => 'A',
            'next_action_date' => '2026-09-10',
        ]);
        $ownLead->assignees()->sync([$other->id]);

        $myLead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'Lead saya',
            'contact_name' => 'B',
            'next_action_date' => '2026-09-11',
        ]);
        $myLead->assignees()->sync([$this->user->id]);

        $this->get(route('dashboard', ['month' => '2026-09', 'mine' => 1]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('events', 1)
                ->where('events.0.title', 'Lead saya'));
    }

    public function test_the_page_can_switch_between_the_calendar_and_the_dated_list(): void
    {
        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('view', 'calendar'));

        $this->get(route('dashboard', ['view' => 'list']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('view', 'list'));

        $this->get(route('dashboard', ['view' => 'apa saja']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('view', 'calendar'));
    }

    public function test_a_month_without_anything_scheduled_comes_back_empty(): void
    {
        $this->get(route('dashboard', ['month' => '2026-12']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('events', 0));
    }
}
