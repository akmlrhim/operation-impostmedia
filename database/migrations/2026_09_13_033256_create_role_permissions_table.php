<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('permission');
            $table->unique(['role', 'permission']);
            $table->timestamps();
        });

        $defaults = [
            'manager' => [
                'manage-master-data',
                'manage-finance',
                'approve-documents',
                'manage-records',
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
        Schema::dropIfExists('role_permissions');
    }
};
