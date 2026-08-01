<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreRegistryDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('documents.manage_versions') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:'.config('registry_documents.max_upload_kb')],
            'change_note' => ['required', 'string', 'max:2000'],
        ];
    }
}
