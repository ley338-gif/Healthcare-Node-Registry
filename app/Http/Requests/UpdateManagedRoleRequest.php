<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateManagedRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && ($this->user()?->can('update', $role) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Role|null $role */ $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('roles', 'name')->ignore($role?->id)],
            'display_name' => ['required', 'string', 'min:2', 'max:160'],
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
