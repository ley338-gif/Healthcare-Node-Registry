<?php

namespace App\Http\Requests;

use App\Models\Manufacturer;
use App\Models\SystemStatus;
use App\Models\SystemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSystemReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = match (true) {
            $this->routeIs('settings.system-types.store') => SystemType::class,
            $this->routeIs('settings.system-statuses.store') => SystemStatus::class,
            default => Manufacturer::class,
        };

        return $this->user()?->can('create', $model) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        [$table, $maxCodeLength, $maxLabelLength] = match (true) {
            $this->routeIs('settings.system-types.store') => ['system_types', 80, 120],
            $this->routeIs('settings.system-statuses.store') => ['system_statuses', 40, 120],
            default => ['manufacturers', 160, 160],
        };

        return [
            'code' => ['required', 'string', "max:{$maxCodeLength}", 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique($table, 'code')],
            'label' => ['required', 'string', 'min:2', "max:{$maxLabelLength}"],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
