<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\User;
use App\Notifications\CrmEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $teammate;

    private LeadStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->teammate = User::factory()->create();
        $this->actingAs($this->user);

        $this->stage = LeadStage::create([
            'name' => 'Prospek', 'slug' => 'prospek', 'color' => '#64748b', 'position' => 0, 'type' => 'open',
        ]);
    }

    public function test_the_new_owner_is_told_when_a_lead_lands_on_them(): void
    {
        $this->post(route('leads.store'), [
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Dilempar',
            'contact_name' => 'Budi',
            'estimated_value' => 0,
            'status' => 'open',
            'temperature' => 'cold',
            'assigned_to_ids' => [$this->teammate->id],
        ])->assertRedirect();

        $notification = $this->teammate->notifications()->firstOrFail();

        $this->assertSame('assigned', $notification->data['kind']);
        $this->assertSame('PT Dilempar', $notification->data['title']);
        $this->assertSame('Lead ditugaskan kepada Anda', $notification->data['subtitle']);
        $this->assertSame(0, $this->user->notifications()->count());
    }

    public function test_taking_a_record_yourself_does_not_send_a_notification(): void
    {
        $this->post(route('leads.store'), [
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Saya Sendiri',
            'contact_name' => 'Budi',
            'estimated_value' => 0,
            'status' => 'open',
            'temperature' => 'cold',
            'assigned_to_ids' => [$this->user->id],
        ])->assertRedirect();

        $this->assertSame(0, $this->user->notifications()->count());
    }

    public function test_saving_a_lead_again_without_changing_the_owner_stays_quiet(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Tetap',
            'contact_name' => 'Budi',
        ]);
        $lead->assignees()->sync([$this->teammate->id]);
        $lead->notifyNewAssignees();

        $this->assertSame(1, $this->teammate->notifications()->count());

        $lead->update(['company_name' => 'PT Tetap Saja']);

        $this->assertSame(1, $this->teammate->fresh()->notifications()->count());
    }

    public function test_signing_a_mou_tells_the_people_behind_it(): void
    {
        $client = Client::create(['company_name' => 'PT Klien', 'short_code' => 'KLI']);

        $contract = Contract::create([
            'number' => 'IM-MOU-0009',
            'client_id' => $client->id,
            'title' => 'Kerja sama',
            'signed_date' => '2026-09-01',
            'status' => 'draft',
        ]);
        $contract->assignees()->sync([$this->teammate->id]);

        $this->teammate->notifications()->delete();

        $this->post(route('contracts.sign', $contract), ['signed_date' => '2026-09-02'])
            ->assertRedirect();

        $notification = $this->teammate->notifications()->firstOrFail();

        $this->assertSame('contract', $notification->data['kind']);
        $this->assertSame('MoU sudah ditandatangani', $notification->data['subtitle']);
    }

    public function test_the_bell_carries_the_unread_count_on_every_page(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Kabar',
            'contact_name' => 'Budi',
        ]);
        $lead->assignees()->sync([$this->teammate->id]);
        $lead->notifyNewAssignees();

        $this->actingAs($this->teammate)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.unread', 1)
                ->where('notifications.items.0.title', 'PT Kabar'));
    }

    public function test_opening_a_notification_marks_it_read_and_lands_on_the_record(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Dibuka',
            'contact_name' => 'Budi',
        ]);
        $lead->assignees()->sync([$this->teammate->id]);
        $lead->notifyNewAssignees();

        $notification = $this->teammate->notifications()->firstOrFail();

        $this->actingAs($this->teammate)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect("/leads/{$lead->id}");

        $this->assertSame(0, $this->teammate->unreadNotifications()->count());
    }

    public function test_a_notification_that_was_already_read_still_lands_on_the_record(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Dibuka Lagi',
            'contact_name' => 'Budi',
        ]);
        $lead->assignees()->sync([$this->teammate->id]);
        $lead->notifyNewAssignees();

        $notification = $this->teammate->notifications()->firstOrFail();
        $notification->markAsRead();

        $this->actingAs($this->teammate)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect("/leads/{$lead->id}");
    }

    public function test_the_stored_destination_is_an_address_inside_the_app(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Alamat',
            'contact_name' => 'Budi',
        ]);
        $lead->assignees()->sync([$this->teammate->id]);
        $lead->notifyNewAssignees();

        $notification = $this->teammate->notifications()->firstOrFail();

        $this->assertSame("/leads/{$lead->id}", $notification->data['url']);
    }

    public function test_an_outside_address_from_the_page_is_ignored(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Jahat',
            'contact_name' => 'Budi',
        ]);
        $lead->assignees()->sync([$this->teammate->id]);
        $lead->notifyNewAssignees();

        $notification = $this->teammate->notifications()->firstOrFail();

        $this->actingAs($this->teammate)
            ->from(route('dashboard'))
            ->post(route('notifications.read', $notification->id), ['to' => '//jahat.test/curi'])
            ->assertRedirect("/leads/{$lead->id}");
    }

    public function test_a_notification_belonging_to_someone_else_leads_nowhere(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Bukan Punya Anda',
            'contact_name' => 'Budi',
        ]);
        $lead->assignees()->sync([$this->teammate->id]);
        $lead->notifyNewAssignees();

        $notification = $this->teammate->notifications()->firstOrFail();

        $this->from(route('dashboard'))
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(1, $this->teammate->unreadNotifications()->count());
    }

    public function test_everything_can_be_marked_read_at_once(): void
    {
        foreach (['PT Satu', 'PT Dua'] as $name) {
            $lead = Lead::create([
                'lead_stage_id' => $this->stage->id,
                'company_name' => $name,
                'contact_name' => 'Budi',
            ]);
            $lead->assignees()->sync([$this->teammate->id]);
            $lead->notifyNewAssignees();
        }

        $this->assertSame(2, $this->teammate->unreadNotifications()->count());

        $this->actingAs($this->teammate)
            ->from(route('dashboard'))
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(0, $this->teammate->fresh()->unreadNotifications()->count());
    }

    public function test_the_notification_builds_a_markdown_email_for_the_recipient(): void
    {
        $mail = (new CrmEvent('lead', 'PT Contoh', 'Lead sudah jadi klien', '/clients/1'))
            ->toMail($this->teammate);

        $this->assertSame('PT Contoh - CV. Impost Media Indonesia', $mail->subject);

        $html = $mail->render();

        $this->assertStringContainsString('CV. Impost Media Indonesia', $html);
        $this->assertStringContainsString('Lead sudah jadi klien', $html);
        $this->assertStringContainsString('Lihat Detail', $html);
    }
}
