<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Department::class) ?? false;
    }

    /** @return array<string,array<int,mixed>> */
    public function rules(): array
    {
        return ['site_id' => ['required', 'integer', 'exists:sites,id'], 'name' => ['required', 'string', 'min:2', 'max:200', Rule::unique('departments', 'name')->where(fn ($q) => $q->where('site_id', $this->integer('site_id')))], 'code' => ['nullable', 'string', 'max:40'], 'specialty' => ['nullable', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:4000']];
    }
}
