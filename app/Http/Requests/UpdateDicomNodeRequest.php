<?php

namespace App\Http\Requests;

use App\Models\DicomNode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDicomNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dicomNode = $this->route('dicomNode');

        return $dicomNode instanceof DicomNode
            && ($this->user()?->can('update', $dicomNode) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var DicomNode|null $dicomNode */
        $dicomNode = $this->route('dicomNode');

        return [
            'name' => [
                'required',
                'string',
                'max:160',
            ],
            'ae_title' => [
                'required',
                'string',
                'max:16',
                'regex:/^[A-Za-z0-9 _-]+$/',
                Rule::unique('dicom_nodes', 'ae_title')
                    ->where('system_id', $dicomNode?->system_id)
                    ->where('host', $this->input('host'))
                    ->where('port', $this->input('port'))
                    ->ignore($dicomNode?->id),
            ],
            'modality' => [
                'nullable',
                'string',
                'max:16',
                'regex:/^[A-Z0-9]+$/i',
            ],
            'host' => [
                'nullable',
                'required_if:role,scp,both',
                'required_with:port',
                'string',
                'max:255',
            ],
            'port' => [
                'nullable',
                'required_if:role,scp,both',
                'integer',
                'between:1,65535',
            ],
            'role' => [
                'required',
                Rule::in([
                    'scu',
                    'scp',
                    'both',
                ]),
            ],
            'status' => [
                'required',
                Rule::in([
                    'active',
                    'planned',
                    'maintenance',
                    'inactive',
                ]),
            ],
            'tls_enabled' => [
                'required',
                'boolean',
            ],
            'tls_profile' => [
                'nullable',
                'required_if:tls_enabled,true',
                'string',
                'max:64',
                'regex:/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}\z/',
            ],
            'supports_echo' => [
                'required',
                'boolean',
            ],
            'supports_store' => [
                'required',
                'boolean',
            ],
            'supports_query' => [
                'required',
                'boolean',
            ],
            'supports_retrieve' => [
                'required',
                'boolean',
            ],
            'supports_storage_commitment' => [
                'required',
                'boolean',
            ],
            'supports_mpps' => [
                'required',
                'boolean',
            ],
            'supports_worklist' => [
                'required',
                'boolean',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ae_title.regex' => 'Der AE Title darf nur Buchstaben, Zahlen, Leerzeichen, Bindestriche und Unterstriche enthalten.',
            'ae_title.unique' => 'Dieser DICOM-Endpunkt ist für das System bereits registriert.',
            'modality.regex' => 'Die Modalität darf nur Buchstaben und Zahlen enthalten (z. B. DX, CT, MR).',
            'host.required_if' => 'Host/IP ist für SCP-Knoten verpflichtend.',
            'host.required_with' => 'Zu einem Port muss ein Host oder eine IP-Adresse angegeben werden.',
            'port.required_if' => 'Der Port ist für SCP-Knoten verpflichtend.',
            'port.between' => 'Der Port muss zwischen 1 und 65535 liegen.',
        ];
    }
}
