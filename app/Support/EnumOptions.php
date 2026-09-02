<?php

namespace App\Support;

use App\Enums\HasLabel;

final class EnumOptions
{
    /**
     * @param  class-string<HasLabel&\BackedEnum>  $enum
     * @return array<int, array{value: string, label: string}>
     */
    public static function from(string $enum): array
    {
        return array_map(
            fn (HasLabel&\BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => $case->label(),
            ],
            $enum::cases(),
        );
    }
}
