<?php

namespace App\Http\Requests;

use App\Models\SystemStatus;
use App\Models\SystemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateSystemReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reference = $this->reference();

        return $reference !== null && ($this->user()?->can('update', $reference) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'min:2', 'max:120'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $reference = $this->reference();
            if ($reference?->is_protected && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'Dieser technische Standardwert kann nicht deaktiviert werden.');
            }
        }];
    }

    private function reference(): SystemType|SystemStatus|null
    {
        $reference = $this->route('systemType') ?? $this->route('systemStatus');

        return $reference instanceof SystemType || $reference instanceof SystemStatus ? $reference : null;
    }
}
