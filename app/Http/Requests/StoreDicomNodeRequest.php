<?php

namespace App\Http\Requests;

use App\Models\DicomNode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDicomNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DicomNode::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $systemId = $this->route('system')?->getKey();

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
                    ->where('system_id', $systemId)
                    ->where('host', $this->input('host'))
                    ->where('port', $this->input('port')),
            ],
            'host' => [
                'required',
                'string',
                'max:255',
            ],
            'port' => [
                'required',
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
            'port.between' => 'Der Port muss zwischen 1 und 65535 liegen.',
        ];
    }
}
