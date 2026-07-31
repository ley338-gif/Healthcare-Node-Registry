<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RunWorklistTestRequest extends FormRequest
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
            'scheduled_station_ae_title' => ['nullable', 'string', 'max:16', 'regex:/^[A-Z0-9 _-]+$/i'],
            'examination_date' => ['required', 'date_format:Y-m-d'],
            'examination_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:examination_date'],
            'modality' => ['nullable', 'string', 'max:16', 'regex:/^[A-Z0-9]+$/i'],
            'patient_name' => ['nullable', 'string', 'max:128'],
            'patient_id' => ['nullable', 'string', 'max:64'],
            'accession_number' => ['nullable', 'string', 'max:64'],
        ];
    }
}
