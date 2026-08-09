<?php

namespace App\Services\Registry;

use App\Models\SystemStatus;
use App\Models\SystemType;
use Illuminate\Database\Eloquent\Collection;

final class SystemReferenceDataService
{
    /** @return list<array{value:string,label:string,is_active:bool}> */
    public function typeOptions(): array
    {
        return $this->options(SystemType::query()->ordered()->get());
    }

    /** @return list<array{value:string,label:string,is_active:bool}> */
    public function statusOptions(): array
    {
        return $this->options(SystemStatus::query()->ordered()->get());
    }

    /**
     * @template TReference of SystemType|SystemStatus
     *
     * @param  Collection<int, TReference>  $values
     * @return list<array{value:string,label:string,is_active:bool}>
     */
    private function options(Collection $values): array
    {
        return $values->map(static fn (SystemType|SystemStatus $value): array => [
            'value' => $value->code,
            'label' => $value->label,
            'is_active' => $value->is_active,
        ])->values()->all();
    }
}
