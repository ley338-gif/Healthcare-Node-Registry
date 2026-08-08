<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSystemNetworkInterfaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('systemNetworkInterface')->system);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $interface = $this->route('systemNetworkInterface');

        return [
            'interface_label' => ['required', 'string', 'max:160', Rule::unique('system_network_interfaces')->where('system_id', $interface->system_id)->ignore($interface->id)],
            'hostname' => ['nullable', 'string', 'max:255', 'required_without_all:fqdn,ip_address'],
            'fqdn' => ['nullable', 'string', 'max:255', 'required_without_all:hostname,ip_address'],
            'ip_address' => ['nullable', 'ip', 'required_without_all:hostname,fqdn'],
            'is_primary' => ['required', 'boolean'],
        ];
    }
}
