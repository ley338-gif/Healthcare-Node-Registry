<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = $this->route('department');

        return $model instanceof Department && ($this->user()?->can('update', $model) ?? false);
    }

    /** @return array<string,array<int,mixed>> */
    public function rules(): array
    {
        $model = $this->route('department');

        return ['site_id' => ['required', 'integer', 'exists:sites,id'], 'name' => ['required', 'string', 'min:2', 'max:200', Rule::unique('departments', 'name')->where(fn ($q) => $q->where('site_id', $this->integer('site_id')))->ignore($model->id)], 'code' => ['nullable', 'string', 'max:40'], 'specialty' => ['nullable', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:4000']];
    }
}
