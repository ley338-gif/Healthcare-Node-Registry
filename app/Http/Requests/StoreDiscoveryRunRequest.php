<?php

namespace App\Http\Requests;

use App\Models\DiscoveryAeCandidate;
use App\Models\DiscoveryRun;
use App\Services\Discovery\NetworkRangeException;
use App\Services\Discovery\NetworkRangeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreDiscoveryRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DiscoveryRun::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:160'],
            'department' => ['nullable', 'string', 'max:160'],
            'ip_range' => ['required', 'string', 'max:64'],
            'exclude_ips' => ['array', 'max:100'],
            'exclude_ips.*' => ['ip'],
            'description' => ['nullable', 'string', 'max:2000'],

            'scan_options' => ['required', 'array'],
            'scan_options.ping_enabled' => ['required', 'boolean'],
            'scan_options.reverse_dns_enabled' => ['required', 'boolean'],
            'scan_options.tcp_scan_enabled' => ['required', 'boolean'],
            'scan_options.dicom_check_enabled' => ['required', 'boolean'],
            'scan_options.scan_unresponsive_hosts' => ['required', 'boolean'],
            'scan_options.max_parallel_hosts' => ['required', 'integer', 'between:1,'.(int) config('discovery.max_parallel_hosts', 16)],
            'scan_options.timeout_seconds' => ['required', 'integer', 'between:1,30'],
            'scan_options.retries' => ['required', 'integer', 'between:0,3'],
            'scan_options.profile' => ['required', Rule::in(['careful', 'standard', 'fast', 'custom'])],

            'ports' => ['required', 'array', 'min:1', 'max:40'],
            'ports.*.port' => ['required', 'integer', 'between:1,65535'],
            'ports.*.protocol' => ['required', Rule::in(['tcp'])],
            'ports.*.label' => ['nullable', 'string', 'max:120'],
            'ports.*.is_dicom_candidate' => ['required', 'boolean'],
            'ports.*.enabled' => ['required', 'boolean'],

            'ae_candidates' => ['array', 'max:30'],
            'ae_candidates.*.ae_title' => ['required', 'string', 'max:16', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'ae_candidates.*.role' => ['required', Rule::in(DiscoveryAeCandidate::ROLES)],
            'ae_candidates.*.source' => ['required', Rule::in(DiscoveryAeCandidate::SOURCES)],

            'confirmed' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmed.accepted' => 'Sie müssen die Berechtigung zum Scannen des Zielbereichs bestätigen.',
            'ports.min' => 'Mindestens ein Port muss ausgewählt werden.',
            'ae_candidates.*.ae_title.regex' => 'AE-Titel enthält ungültige Zeichen.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var list<array<string, mixed>> $ports */
                $ports = $this->input('ports', []);
                $enabledPorts = collect($ports)->where('enabled', true);
                if ($enabledPorts->isEmpty()) {
                    $validator->errors()->add('ports', 'Mindestens ein Port muss aktiviert sein.');

                    return;
                }

                try {
                    app(NetworkRangeService::class)->validate(
                        $this->string('ip_range')->toString(),
                        (array) $this->input('exclude_ips', []),
                    );
                } catch (NetworkRangeException $exception) {
                    $validator->errors()->add('ip_range', $exception->getMessage());
                }
            },
        ];
    }
}
