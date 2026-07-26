<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('system'),
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'department_id' => ['nullable', 'exists:departments,id'],

            'name' => ['required', 'string', 'max:160'],
            'system_type' => ['required', 'string', 'max:80'],
            'status' => ['required', 'string', 'max:40'],

            'hostname' => ['nullable', 'string', 'max:255'],
            'fqdn' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],

            'vendor' => ['nullable', 'string', 'max:160'],
            'product' => ['nullable', 'string', 'max:160'],
            'model' => ['nullable', 'string', 'max:160'],
            'version' => ['nullable', 'string', 'max:120'],

            'operating_system' => ['nullable', 'string', 'max:160'],
            'operating_system_version' => ['nullable', 'string', 'max:120'],

            'serial_number' => ['nullable', 'string', 'max:160'],
            'inventory_number' => ['nullable', 'string', 'max:160'],

            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
