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

        return $this->rulesForSystem(is_int($systemId) ? $systemId : null, $this->input('host'), $this->input('port'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rulesForSystem(?int $systemId, mixed $host = null, mixed $port = null): array
    {

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
                    ->where('host', $host)
                    ->where('port', $port),
            ],
            'modality' => [
                'nullable',
                'string',
                'max:16',
                'regex:/^[A-Z0-9]+$/i',
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
            'modality.regex' => 'Die Modalität darf nur Buchstaben und Zahlen enthalten (z. B. DX, CT, MR).',
            'port.between' => 'Der Port muss zwischen 1 und 65535 liegen.',
        ];
    }
}
