<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PreviewRegistryImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('registry.manage') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['systems', 'dicom_nodes'])],
            'csv_file' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
        ];
    }
}
