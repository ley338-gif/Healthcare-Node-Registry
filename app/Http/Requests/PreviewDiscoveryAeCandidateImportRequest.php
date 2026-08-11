<?php

namespace App\Http\Requests;

use App\Models\DiscoveryRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class PreviewDiscoveryAeCandidateImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DiscoveryRun::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'ae_candidate_file' => ['required', 'file', 'max:2048'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $extension = strtolower((string) $this->file('ae_candidate_file')?->getClientOriginalExtension());
                if (! in_array($extension, ['csv', 'xlsx'], true)) {
                    $validator->errors()->add('ae_candidate_file', 'Nur CSV- oder XLSX-Dateien sind erlaubt.');
                }
            },
        ];
    }
}
