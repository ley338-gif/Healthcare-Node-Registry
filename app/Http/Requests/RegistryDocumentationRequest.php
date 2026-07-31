<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegistryDocumentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'documentation_type' => ['required', 'string', 'max:80'],
            'section' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_-]+$/'],
            'title' => ['required', 'string', 'max:200'],
            'content' => ['nullable', 'string', 'max:100000'],
            'structured_data' => ['sometimes', 'array'],
            'visibility' => ['required', 'in:internal,restricted'],
        ];
    }
}
