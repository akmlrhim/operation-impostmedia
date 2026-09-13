<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->resetDefaults();
    }

    private function resetDefaults(): void
    {
        DB::table('role_permissions')->whereNot('role', UserRole::Superuser->value)->delete();

        $defaults = [
            UserRole::Manager->value => array_map(
                fn (Permission $permission): string => $permission->value,
                array_filter(
                    Permission::cases(),
                    fn (Permission $permission): bool => $permission !== Permission::ManageUsers,
                ),
            ),
            UserRole::Member->value => [
                Permission::CreateLeads->value,
                Permission::UpdateLeads->value,
                Permission::MoveLeads->value,
                Permission::CreateClients->value,
                Permission::UpdateClients->value,
                Permission::CreateContracts->value,
                Permission::CreateInvoices->value,
            ],
        ];

        foreach ($defaults as $role => $permissions) {
            foreach ($permissions as $permission) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role' => $role,
                    'permission' => $permission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
