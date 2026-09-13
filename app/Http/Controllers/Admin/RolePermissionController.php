<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Models\RolePermission;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('access/index', [
            'roles' => array_map(
                fn (UserRole $role): array => [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'locked' => $role === UserRole::Superuser,
                ],
                UserRole::cases(),
            ),
            'permissions' => RolePermission::options(),
            'matrix' => RolePermission::matrix(),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request): RedirectResponse
    {
        $permissions = $request->validated('permissions');

        foreach ([UserRole::Manager, UserRole::Member] as $role) {
            RolePermission::sync($role, $permissions[$role->value] ?? []);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Hak akses per peran sudah diperbarui.',
        ]);

        return back();
    }
}
