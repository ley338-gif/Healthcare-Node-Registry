<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RunStorageTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'confirmed' => ['required', 'accepted'],
            'calling_ae_title' => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9 _-]+$/i'],
            'called_ae_title' => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9 _-]+$/i'],
        ];
    }
}
