<?php

namespace Tests\Feature\Crm;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LeadTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Lead $lead;

    private ServicePackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $stage = LeadStage::create([
            'name' => 'Prospek', 'slug' => 'prospek', 'color' => '#64748b', 'position' => 0, 'type' => 'open',
        ]);

        $service = Service::create(['type' => 'umkm', 'name' => 'Legalitas']);
        $this->package = ServicePackage::create([
            'service_id' => $service->id, 'name' => 'Paket NIB', 'price' => 750000, 'unit' => 'paket',
        ]);

        $this->lead = Lead::create([
            'lead_stage_id' => $stage->id,
            'company_name' => 'PT Kopi Nusantara',
            'contact_name' => 'Dewi Lestari',
            'estimated_value' => 750000,
        ]);

        $this->lead->servicePackages()->attach($this->package->id, ['position' => 0]);
    }

    public function test_detail_page_carries_the_lead_and_its_timeline(): void
    {
        $this->post(route('activities.store', $this->lead), [
            'type' => 'meeting',
            'title' => 'Meeting di kantor klien',
            'description' => "Hadir: Dewi, Rahim.\nBahas paket NIB.",
            'scheduled_at' => '2026-09-05 10:00',
            'completed' => true,
        ])->assertRedirect();

        $this->get(route('leads.show', $this->lead))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('leads/show')
                ->where('lead.company_name', 'PT Kopi Nusantara')
                ->where('lead.services.0.price', 750000)
                ->where('lead.services.0.name', 'Paket NIB')
                ->where('lead.services.0.service_name', 'Legalitas')
                ->has('lead.attachments')
                ->has('services')
                ->has('activityTypes')
                ->has('timeline', 1)
                ->where('timeline.0.title', 'Meeting di kantor klien')
                ->where('timeline.0.type', 'meeting')
                ->where('timeline.0.type_label', 'Meeting')
                ->where('timeline.0.description', "Hadir: Dewi, Rahim.\nBahas paket NIB.")
                ->where('timeline.0.user.name', $this->user->name)
                ->etc());

        $activity = Activity::firstOrFail();

        $this->assertNotNull($activity->completed_at);
        $this->assertSame(Lead::class, $activity->subject_type);
        $this->assertSame($this->lead->id, $activity->subject_id);
    }

    public function test_timeline_puts_the_newest_entry_first(): void
    {
        foreach ([['Kontak awal', '2026-09-01 09:00'], ['Kirim penawaran', '2026-09-06 14:00']] as [$title, $at]) {
            $this->post(route('activities.store', $this->lead), [
                'type' => 'follow_up',
                'title' => $title,
                'scheduled_at' => $at,
                'completed' => true,
            ])->assertRedirect();
        }

        $this->get(route('leads.show', $this->lead))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('timeline.0.title', 'Kirim penawaran')
                ->where('timeline.1.title', 'Kontak awal')
                ->etc());
    }

    public function test_an_entry_without_the_completed_flag_stays_open(): void
    {
        $this->post(route('activities.store', $this->lead), [
            'type' => 'call',
            'title' => 'Telepon ulang minggu depan',
            'scheduled_at' => '2026-09-20 09:00',
            'completed' => false,
        ])->assertRedirect();

        $activity = Activity::firstOrFail();
        $this->assertNull($activity->completed_at);

        $this->post(route('activities.toggle', $activity))->assertRedirect();
        $this->assertNotNull($activity->fresh()->completed_at);

        $this->post(route('activities.toggle', $activity))->assertRedirect();
        $this->assertNull($activity->fresh()->completed_at);
    }

    public function test_editing_an_entry_keeps_the_original_completion_time(): void
    {
        $this->post(route('activities.store', $this->lead), [
            'type' => 'note', 'title' => 'Catatan awal', 'completed' => true,
        ])->assertRedirect();

        $activity = Activity::firstOrFail();
        $completedAt = $activity->completed_at;

        $this->travel(2)->days();

        $this->put(route('activities.update', $activity), [
            'type' => 'note', 'title' => 'Catatan awal diperbaiki', 'completed' => true,
        ])->assertRedirect();

        $activity->refresh();

        $this->assertSame('Catatan awal diperbaiki', $activity->title);
        $this->assertTrue($completedAt->equalTo($activity->completed_at));
    }

    public function test_entry_requires_a_title_and_a_known_type(): void
    {
        $this->post(route('activities.store', $this->lead), ['type' => 'meeting', 'title' => ''])
            ->assertSessionHasErrors('title');

        $this->post(route('activities.store', $this->lead), ['type' => 'ngobrol', 'title' => 'Halo'])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, Activity::query()->count());
    }

    public function test_entry_can_be_deleted(): void
    {
        $this->post(route('activities.store', $this->lead), [
            'type' => 'note', 'title' => 'Salah catat', 'completed' => true,
        ])->assertRedirect();

        $this->delete(route('activities.destroy', Activity::firstOrFail()))->assertRedirect();

        $this->assertSame(0, Activity::query()->count());
    }

    public function test_timeline_survives_a_soft_deleted_lead_and_comes_back_on_restore(): void
    {
        $this->post(route('activities.store', $this->lead), [
            'type' => 'note', 'title' => 'Catatan', 'completed' => true,
        ])->assertRedirect();

        $this->delete(route('leads.destroy', $this->lead))->assertRedirect();

        $this->assertSame(0, Lead::query()->count());
        $this->assertSame(1, Activity::query()->count());

        Lead::withTrashed()->findOrFail($this->lead->id)->restore();

        $this->get(route('leads.show', $this->lead))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('timeline', 1)
                ->where('timeline.0.title', 'Catatan')
                ->etc());
    }
}
