<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_superuser_reaches_the_user_list(): void
    {
        $this->actingAs($this->superuser())->get(route('users.index'))->assertOk();

        foreach ([UserRole::Manager, UserRole::Member] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('users.index'))
                ->assertForbidden();
        }
    }

    public function test_a_pending_registrant_never_reaches_it_even_as_superuser(): void
    {
        $waiting = User::factory()->pending()->create(['role' => UserRole::Superuser]);

        $this->actingAs($waiting)->get(route('users.index'))->assertRedirect(route('pending'));
    }

    public function test_approving_a_registrant_grants_the_chosen_role(): void
    {
        $pending = User::factory()->pending()->create(['role' => UserRole::Member]);

        $this->actingAs($this->superuser())
            ->post(route('users.approve', $pending), ['role' => 'manager', 'is_active' => true]);

        $pending->refresh();

        $this->assertNotNull($pending->approved_at);
        $this->assertSame(UserRole::Manager, $pending->role);
        $this->assertTrue($pending->is_active);
    }

    public function test_an_already_approved_user_is_not_approved_twice(): void
    {
        $approved = User::factory()->create(['role' => UserRole::Member]);
        $approvedAt = $approved->approved_at;

        $this->actingAs($this->superuser())
            ->post(route('users.approve', $approved), ['role' => 'superuser', 'is_active' => true]);

        $approved->refresh();

        $this->assertSame(UserRole::Member, $approved->role);
        $this->assertEquals($approvedAt, $approved->approved_at);
    }

    public function test_a_superuser_cannot_strip_their_own_authority(): void
    {
        $superuser = $this->superuser();

        $this->actingAs($superuser)
            ->put(route('users.update', $superuser), ['role' => 'member', 'is_active' => true]);

        $this->assertSame(UserRole::Superuser, $superuser->fresh()->role);

        $this->actingAs($superuser)
            ->put(route('users.update', $superuser), ['role' => 'superuser', 'is_active' => false]);

        $this->assertTrue($superuser->fresh()->is_active);
    }

    public function test_rejecting_a_pending_registrant_removes_the_row(): void
    {
        $pending = User::factory()->pending()->create();

        $this->actingAs($this->superuser())->delete(route('users.destroy', $pending));

        $this->assertDatabaseMissing('users', ['id' => $pending->id]);
    }

    public function test_an_approved_user_can_be_deleted_and_their_work_stays(): void
    {
        $member = User::factory()->create(['role' => UserRole::Member]);

        $lead = Lead::create([
            'lead_stage_id' => LeadStage::create([
                'name' => 'Prospek', 'slug' => 'prospek', 'color' => '#64748b', 'position' => 0, 'type' => 'open',
            ])->id,
            'company_name' => 'PT Ditinggal',
            'contact_name' => 'Budi',
            'created_by' => $member->id,
        ]);
        $lead->assignees()->sync([$member->id]);

        $this->actingAs($this->superuser())->delete(route('users.destroy', $member));

        $this->assertDatabaseMissing('users', ['id' => $member->id]);

        $lead->refresh();

        $this->assertSame(0, $lead->assignees()->count());
        $this->assertNull($lead->created_by);
    }

    public function test_nobody_can_delete_their_own_account_from_the_list(): void
    {
        $superuser = $this->superuser();

        $this->actingAs($superuser)->delete(route('users.destroy', $superuser));

        $this->assertDatabaseHas('users', ['id' => $superuser->id]);
    }

    public function test_the_last_superuser_cannot_be_deleted(): void
    {
        $superuser = $this->superuser();
        $other = User::factory()->create(['role' => UserRole::Superuser]);

        $this->actingAs($superuser)->delete(route('users.destroy', $other));
        $this->assertDatabaseMissing('users', ['id' => $other->id]);

        $manager = User::factory()->create(['role' => UserRole::Manager]);

        $this->actingAs($manager)->delete(route('users.destroy', $superuser))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $superuser->id]);
    }

    public function test_only_a_superuser_may_approve_a_registrant(): void
    {
        $pending = User::factory()->pending()->create();

        foreach ([UserRole::Manager, UserRole::Member] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post(route('users.approve', $pending), ['role' => 'manager', 'is_active' => true])
                ->assertForbidden();
        }

        $this->assertNull($pending->fresh()->approved_at);
    }

    public function test_only_a_superuser_may_edit_or_delete_a_user(): void
    {
        $target = User::factory()->create(['role' => UserRole::Member]);

        foreach ([UserRole::Manager, UserRole::Member] as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $this->actingAs($actor)
                ->put(route('users.update', $target), ['role' => 'manager', 'is_active' => true])
                ->assertForbidden();

            $this->actingAs($actor)->delete(route('users.destroy', $target))->assertForbidden();
        }

        $this->assertSame(UserRole::Member, $target->fresh()->role);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_an_unknown_role_is_rejected(): void
    {
        $pending = User::factory()->pending()->create();

        $this->actingAs($this->superuser())
            ->post(route('users.approve', $pending), ['role' => 'staff', 'is_active' => true])
            ->assertSessionHasErrors('role');

        $this->assertNull($pending->fresh()->approved_at);
    }

    private function superuser(): User
    {
        return User::factory()->create(['role' => UserRole::Superuser]);
    }
}
