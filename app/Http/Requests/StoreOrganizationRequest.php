<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

final class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
    }

    /** @return array<string,array<int,string>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:200', 'unique:organizations,name'], 'short_name' => ['nullable', 'string', 'max:40'], 'description' => ['nullable', 'string', 'max:4000']];
    }
}
