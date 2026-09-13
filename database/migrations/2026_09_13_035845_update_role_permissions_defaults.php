<?php

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
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
                DB::table('role_permissions')->insert([
                    'role' => $role,
                    'permission' => $permission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereNot('role', UserRole::Superuser->value)->delete();

        $oldDefaults = [
            'manage-master-data',
            'manage-finance',
            'approve-documents',
            'manage-records',
        ];

        foreach ($oldDefaults as $permission) {
            DB::table('role_permissions')->insert([
                'role' => UserRole::Manager->value,
                'permission' => $permission,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
