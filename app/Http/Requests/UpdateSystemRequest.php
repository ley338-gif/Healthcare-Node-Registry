<?php

namespace App\Http\Requests;

use App\Models\System;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $system = $this->route('system');
        $currentType = $system instanceof System ? $system->system_type : null;
        $currentStatus = $system instanceof System ? $system->status : null;
        $currentVendor = $system instanceof System ? $system->vendor : null;

        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'department_id' => ['nullable', 'exists:departments,id'],

            'name' => ['required', 'string', 'max:160'],
            'system_type' => [
                'required', 'string', 'max:80',
                Rule::exists('system_types', 'code')->where(
                    fn ($query) => $query->where('is_active', true)->when(
                        $currentType !== null,
                        fn ($nested) => $nested->orWhere('code', $currentType),
                    ),
                ),
            ],
            'status' => [
                'required', 'string', 'max:40',
                Rule::exists('system_statuses', 'code')->where(
                    fn ($query) => $query->where('is_active', true)->when(
                        $currentStatus !== null,
                        fn ($nested) => $nested->orWhere('code', $currentStatus),
                    ),
                ),
            ],
            'responsible' => ['nullable', 'string', 'max:160'],
            'criticality' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],

            'hostname' => ['nullable', 'string', 'max:255'],
            'fqdn' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],

            'vendor' => [
                'nullable', 'string', 'max:160',
                Rule::exists('manufacturers', 'code')->where(
                    fn ($query) => $query->where('is_active', true)->when(
                        $currentVendor !== null,
                        fn ($nested) => $nested->orWhere('code', $currentVendor),
                    ),
                ),
            ],
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
