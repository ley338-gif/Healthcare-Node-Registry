<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\RegistryPasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

final class ResetManagedUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('resetPassword', $user) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['password' => RegistryPasswordPolicy::rules()];
    }
}
