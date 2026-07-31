<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AnalyzeDicomFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('tests.analyze_file') ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['dicom_file' => ['required', 'file', 'max:20480']];
    }
}
