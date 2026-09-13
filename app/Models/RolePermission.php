<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['role', 'permission'];

    /**
     * @return array<int, array{value: string, label: string, description: string, group: string}>
     */
    public static function options(): array
    {
        $options = array_map(
            fn (Permission $permission): array => [
                'value' => $permission->value,
                'label' => $permission->label(),
                'description' => $permission->description(),
                'group' => $permission->group(),
            ],
            Permission::cases(),
        );

        usort($options, fn (array $a, array $b): int => [$a['group'], $a['label']] <=> [$b['group'], $b['label']]);

        return $options;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function matrix(): array
    {
        $matrix = collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => []])
            ->all();

        foreach (self::query()->orderBy('role')->orderBy('permission')->get() as $grant) {
            $matrix[$grant->role][] = $grant->permission;
        }

        return $matrix;
    }

    public static function grants(UserRole $role): array
    {
        return self::query()
            ->where('role', $role->value)
            ->orderBy('permission')
            ->pluck('permission')
            ->all();
    }

    public static function sync(UserRole $role, array $permissions): void
    {
        $permissions = array_values(array_unique($permissions));

        self::query()->where('role', $role->value)->delete();

        foreach ($permissions as $permission) {
            self::query()->create([
                'role' => $role->value,
                'permission' => $permission,
            ]);
        }
    }
}
