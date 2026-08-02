<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\RegistryPasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

final class StoreManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => RegistryPasswordPolicy::rules(),
            'is_active' => ['required', 'boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }
}
