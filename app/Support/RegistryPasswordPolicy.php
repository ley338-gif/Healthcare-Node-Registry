<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class RegistryPasswordPolicy
{
    /** @return list<mixed> */
    public static function rules(bool $confirmed = true): array
    {
        return array_values(array_filter([
            'required', 'string', Password::min(14)->mixedCase()->numbers()->symbols(), $confirmed ? 'confirmed' : null,
        ]));
    }
}
