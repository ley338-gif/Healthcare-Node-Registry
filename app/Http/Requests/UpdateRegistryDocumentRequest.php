<?php

namespace App\Http\Requests;

use App\Support\RegistryDocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRegistryDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('documents.update') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category' => ['required', Rule::enum(RegistryDocumentCategory::class)],
            'visibility' => ['required', Rule::in(['internal', 'restricted'])],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'contract_reference' => ['nullable', 'string', 'max:200'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:80'],
        ];
    }
}
