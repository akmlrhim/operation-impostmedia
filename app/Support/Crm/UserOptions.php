<?php

namespace App\Support\Crm;

use App\Models\User;

class UserOptions
{
    /**
     * Anggota yang bisa dipilih jadi penanggung jawab.
     *
     * @return list<array{value: int, label: string}>
     */
    public static function assignable(): array
    {
        return array_values(User::query()
            ->where('is_active', true)
            ->whereNotNull('approved_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'value' => $user->id,
                'label' => $user->name,
            ])
            ->all());
    }
}
