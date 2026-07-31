<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveDiagnosticTestProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'test_type' => ['required', Rule::in(['network', 'dicom_echo', 'worklist', 'pacs_query'])],
            'dicom_node_public_id' => ['required', 'uuid', 'exists:dicom_nodes,public_id'],
            'calling_ae_title' => ['nullable', 'string', 'max:16', 'regex:/^[A-Z0-9 _-]+$/i'],
            'configuration' => ['present', 'array'],
            'configuration.*' => ['nullable', 'string', 'max:160'],
            'timeout_seconds' => ['required', 'integer', 'between:1,60'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
