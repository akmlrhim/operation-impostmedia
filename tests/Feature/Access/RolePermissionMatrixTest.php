<?php

namespace Tests\Feature\Access;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $superuser;

    private User $manager;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);

        $this->superuser = User::factory()->create(['role' => UserRole::Superuser]);
        $this->manager = User::factory()->create(['role' => UserRole::Manager]);
        $this->member = User::factory()->create(['role' => UserRole::Member]);
    }

    public function test_only_a_superuser_can_open_the_access_page(): void
    {
        $managerDefaults = array_values(array_filter(
            array_map(fn (Permission $permission): string => $permission->value, Permission::cases()),
            fn (string $value): bool => $value !== Permission::ManageUsers->value,
        ));
        sort($managerDefaults);

        $memberDefaults = [
            Permission::CreateLeads->value,
            Permission::UpdateLeads->value,
            Permission::MoveLeads->value,
            Permission::CreateClients->value,
            Permission::UpdateClients->value,
            Permission::CreateContracts->value,
            Permission::CreateInvoices->value,
        ];
        sort($memberDefaults);

        $this->actingAs($this->superuser)
            ->get(route('access.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('roles', 3)
                ->has('permissions', count(Permission::cases()))
                ->where('matrix.superuser', [])
                ->where('matrix.manager', $managerDefaults)
                ->where('matrix.member', $memberDefaults)
            );
    }

    public function test_manager_and_member_are_rejected_from_the_access_page(): void
    {
        $this->actingAs($this->manager)->get(route('access.index'))->assertForbidden();
        $this->actingAs($this->member)->get(route('access.index'))->assertForbidden();
    }

    public function test_a_superuser_can_save_the_permission_matrix(): void
    {
        $payload = [
            'permissions' => [
                'manager' => ['view-finance'],
                'member' => ['create-leads'],
            ],
        ];

        $this->actingAs($this->superuser)
            ->put(route('access.update'), $payload)
            ->assertRedirect();

        $this->assertSame(['view-finance'], $this->grants(UserRole::Manager));
        $this->assertSame(['create-leads'], $this->grants(UserRole::Member));

        $this->actingAs($this->manager)->get(route('finance.dashboard'))->assertOk();
        $this->actingAs($this->member)->get(route('finance.dashboard'))->assertForbidden();
    }

    public function test_revoking_a_permission_immediately_flips_the_associated_gate(): void
    {
        $payload = [
            'permissions' => [
                'manager' => [],
                'member' => [],
            ],
        ];

        $this->actingAs($this->superuser)
            ->put(route('access.update'), $payload)
            ->assertRedirect();

        $this->actingAs($this->manager);
        $this->assertFalse($this->manager->can('manage-finance'));
        $this->get(route('finance.dashboard'))->assertForbidden();
    }

    public function test_a_superuser_stays_rooted_even_when_the_matrix_is_empty(): void
    {
        $payload = [
            'permissions' => [
                'manager' => [],
                'member' => [],
            ],
        ];

        $this->actingAs($this->superuser)
            ->put(route('access.update'), $payload)
            ->assertRedirect();

        $this->actingAs($this->superuser);
        $this->assertTrue($this->superuser->can(Permission::ManageFinance->value));
        $this->get(route('finance.dashboard'))->assertOk();
    }

    public function test_invalid_permission_values_are_rejected(): void
    {
        $payload = [
            'permissions' => [
                'manager' => ['not-a-real-permission'],
                'member' => [],
            ],
        ];

        $this->actingAs($this->superuser)
            ->put(route('access.update'), $payload)
            ->assertSessionHasErrors('permissions.manager.0');

        $this->assertNotContains('not-a-real-permission', $this->grants(UserRole::Manager));
    }

    public function test_a_guest_is_redirected_away_from_the_access_page(): void
    {
        $this->get(route('access.index'))->assertRedirect(route('login'));
    }

    public function test_manager_losing_manage_users_cannot_touch_user_routes(): void
    {
        User::factory()->pending()->create();

        $payload = [
            'permissions' => [
                'manager' => ['manage-finance'],
                'member' => [],
            ],
        ];

        $this->actingAs($this->superuser)
            ->put(route('access.update'), $payload)
            ->assertRedirect();

        $this->actingAs($this->manager)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    private function grants(UserRole $role): array
    {
        return RolePermission::grants($role);
    }
}
