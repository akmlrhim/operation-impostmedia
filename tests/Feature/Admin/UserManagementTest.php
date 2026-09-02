<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_superuser_reaches_the_user_list(): void
    {
        $this->actingAs($this->superuser())->get(route('users.index'))->assertOk();

        foreach ([UserRole::Administrator, UserRole::Manager, UserRole::Member] as $role) {
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

    public function test_an_approved_user_is_deactivated_instead_of_deleted(): void
    {
        $member = User::factory()->create(['role' => UserRole::Member]);

        $this->actingAs($this->superuser())->delete(route('users.destroy', $member));

        $this->assertDatabaseHas('users', ['id' => $member->id]);
        $this->assertFalse($member->fresh()->is_active);
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
