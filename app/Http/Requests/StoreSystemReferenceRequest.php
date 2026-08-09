<?php

namespace App\Http\Requests;

use App\Models\SystemStatus;
use App\Models\SystemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSystemReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = $this->routeIs('settings.system-types.store') ? SystemType::class : SystemStatus::class;

        return $this->user()?->can('create', $model) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $table = $this->routeIs('settings.system-types.store') ? 'system_types' : 'system_statuses';
        $maxCodeLength = $table === 'system_types' ? 80 : 40;

        return [
            'code' => ['required', 'string', "max:{$maxCodeLength}", 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique($table, 'code')],
            'label' => ['required', 'string', 'min:2', 'max:120'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
