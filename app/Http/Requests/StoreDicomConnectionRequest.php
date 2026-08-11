<?php

namespace App\Http\Requests;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreDicomConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            DicomConnection::class,
        ) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'source_dicom_node_id' => [
                'required',
                'integer',
                Rule::exists('dicom_nodes', 'id')
                    ->whereNull('archived_at'),
            ],
            'target_dicom_node_id' => [
                'required',
                'integer',
                Rule::exists('dicom_nodes', 'id')
                    ->whereNull('archived_at'),
                Rule::notIn([
                    $this->integer('source_dicom_node_id'),
                ]),
            ],
            'destination_dicom_node_id' => [
                'nullable',
                'integer',
                Rule::exists('dicom_nodes', 'id')
                    ->whereNull('archived_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:160',
            ],
            'service' => [
                'required',
                Rule::in(DicomConnection::SERVICES),
                Rule::unique('dicom_connections', 'service')
                    ->where(
                        'source_dicom_node_id',
                        $this->integer('source_dicom_node_id'),
                    )
                    ->where(
                        'target_dicom_node_id',
                        $this->integer('target_dicom_node_id'),
                    ),
            ],
            'status' => [
                'required',
                Rule::in([
                    'active',
                    'planned',
                    'maintenance',
                    'inactive',
                ]),
            ],
            'evidence_status' => [
                'required',
                Rule::in(DicomConnection::EVIDENCE_STATUSES),
            ],
            'calling_ae_title' => [
                'nullable',
                'string',
                'max:16',
                'regex:/^[A-Za-z0-9 _-]+$/',
            ],
            'called_ae_title' => [
                'nullable',
                'string',
                'max:16',
                'regex:/^[A-Za-z0-9 _-]+$/',
            ],
            'port_override' => [
                'nullable',
                'integer',
                'between:1,65535',
            ],
            'tls_enabled' => [
                'required',
                'boolean',
            ],
            'tls_profile' => [
                'nullable',
                'required_if:tls_enabled,true',
                'string',
                'max:64',
                'regex:/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}\z/',
            ],
            'test_enabled' => [
                'required',
                'boolean',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $service = $this->string('service')->toString();
                $destinationId = $this->input(
                    'destination_dicom_node_id',
                );

                if (
                    $service === DicomConnection::SERVICE_MOVE
                    && blank($destinationId)
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'destination_dicom_node_id',
                            'Für C-MOVE muss ein Zielknoten angegeben werden.',
                        );
                }

                if (
                    $service !== DicomConnection::SERVICE_MOVE
                    && filled($destinationId)
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'destination_dicom_node_id',
                            'Ein separater Zielknoten ist nur bei C-MOVE erlaubt.',
                        );
                }

                $this->validateAddressableNode(
                    $validator,
                    'target_dicom_node_id',
                    $this->input('target_dicom_node_id'),
                    $this->integer('port_override') ?: null,
                );

                if ($service === DicomConnection::SERVICE_MOVE) {
                    $this->validateAddressableNode(
                        $validator,
                        'destination_dicom_node_id',
                        $destinationId,
                    );
                }
            },
        ];
    }

    private function validateAddressableNode(
        Validator $validator,
        string $field,
        mixed $nodeId,
        ?int $portOverride = null,
    ): void {
        if (! is_numeric($nodeId)) {
            return;
        }

        $node = DicomNode::query()->find((int) $nodeId);

        if ($node !== null && ! $node->hasNetworkEndpoint($portOverride)) {
            $validator->errors()->add(
                $field,
                'Der Zielknoten benötigt einen Host und einen DICOM-Port oder Port-Override.',
            );
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_dicom_node_id.not_in' => 'Quell- und Zielknoten dürfen nicht identisch sein.',

            'service.unique' => 'Diese Verbindung ist für den Dienst bereits registriert.',

            'calling_ae_title.regex' => 'Der Calling AE Title enthält ungültige Zeichen.',

            'called_ae_title.regex' => 'Der Called AE Title enthält ungültige Zeichen.',

            'port_override.between' => 'Der Port muss zwischen 1 und 65535 liegen.',
        ];
    }
}
