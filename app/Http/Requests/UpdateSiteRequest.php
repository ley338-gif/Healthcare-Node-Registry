<?php

namespace App\Http\Requests;

use App\Models\Site;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = $this->route('site');

        return $model instanceof Site && ($this->user()?->can('update', $model) ?? false);
    }

    /** @return array<string,array<int,mixed>> */
    public function rules(): array
    {
        $model = $this->route('site');

        return ['organization_id' => ['required', 'integer', 'exists:organizations,id'], 'name' => ['required', 'string', 'min:2', 'max:200', Rule::unique('sites', 'name')->where(fn ($q) => $q->where('organization_id', $this->integer('organization_id')))->ignore($model->id)], 'code' => ['nullable', 'string', 'max:40'], 'street' => ['nullable', 'string', 'max:200'], 'postal_code' => ['nullable', 'string', 'max:20'], 'city' => ['nullable', 'string', 'max:120'], 'country_code' => ['required', 'string', 'size:2'], 'timezone' => ['required', 'timezone:all'], 'description' => ['nullable', 'string', 'max:4000']];
    }
}
