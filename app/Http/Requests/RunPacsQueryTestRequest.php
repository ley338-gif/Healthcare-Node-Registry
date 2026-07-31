<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RunPacsQueryTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'calling_ae_title' => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9 _-]+$/i'],
            'called_ae_title' => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9 _-]+$/i'],
            'patient_name' => ['nullable', 'string', 'max:128'],
            'patient_id' => ['nullable', 'string', 'max:64'],
            'accession_number' => ['nullable', 'string', 'max:64'],
            'study_instance_uid' => ['nullable', 'string', 'max:64', 'regex:/^[0-9]+(?:\.[0-9]+)+$/'],
            'modality' => ['nullable', 'string', 'max:16', 'regex:/^[A-Z0-9]+$/i'],
            'study_date' => ['nullable', 'date_format:Y-m-d'],
            'study_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:study_date'],
            'study_description' => ['nullable', 'string', 'max:128'],
        ];
    }
}
