<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = $this->route('organization');

        return $model instanceof Organization && ($this->user()?->can('update', $model) ?? false);
    }

    /** @return array<string,array<int,mixed>> */
    public function rules(): array
    {
        $model = $this->route('organization');

        return ['name' => ['required', 'string', 'min:2', 'max:200', Rule::unique('organizations', 'name')->ignore($model->id)], 'short_name' => ['nullable', 'string', 'max:40'], 'description' => ['nullable', 'string', 'max:4000']];
    }
}
