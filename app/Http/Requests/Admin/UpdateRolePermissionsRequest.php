<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolePermissionsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roles = array_column(UserRole::cases(), 'value');

        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => [Rule::enum(Permission::class)],
        ];
    }
}
