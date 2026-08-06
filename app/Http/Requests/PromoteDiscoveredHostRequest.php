<?php

namespace App\Http\Requests;

use App\Models\DiscoveredHost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PromoteDiscoveredHostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $host = $this->route('discoveredHost');

        return $host instanceof DiscoveredHost && ($this->user()?->can('promote', $host) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['create', 'update_existing'])],
            'existing_system_id' => ['required_if:action,update_existing', 'nullable', 'integer', 'exists:systems,id'],

            'name' => ['required', 'string', 'max:160'],
            'system_type' => ['required', 'string', 'max:80'],
            'vendor' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'operational_status' => ['required', Rule::in(['active', 'planned', 'maintenance', 'inactive'])],
            'criticality' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'responsible' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'ae_title' => ['required', 'string', 'max:16', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'dicom_node_name' => ['nullable', 'string', 'max:160'],
            'role' => ['required', Rule::in(['scu', 'scp', 'both'])],

            'acknowledged_duplicates' => ['boolean'],
        ];
    }
}
