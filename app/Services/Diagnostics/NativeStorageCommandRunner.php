<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use App\Services\Dicom\DicomTlsConfigurationException;
use App\Services\Dicom\DicomTlsProfileResolver;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class NativeStorageCommandRunner implements StorageCommandRunner
{
    public function __construct(private readonly DicomTlsProfileResolver $tlsProfiles = new DicomTlsProfileResolver) {}

    public function run(
        DicomNode $node,
        string $callingAeTitle,
        string $calledAeTitle,
        SyntheticStorageSopClass $sopClass = SyntheticStorageSopClass::SecondaryCapture,
    ): StorageCommandResult {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-store-'.Str::random(20);
        $dicomPath = $directory.DIRECTORY_SEPARATOR.'test.dcm';
        $uidRoot = '1.2.826.0.1.3680043.10.987';
        $suffix = now()->format('YmdHis').random_int(100000, 999999);
        $studyUid = "{$uidRoot}.1.{$suffix}";
        $seriesUid = "{$uidRoot}.2.{$suffix}";
        $instanceUid = "{$uidRoot}.3.{$suffix}";
        $frameOfReferenceUid = "{$uidRoot}.4.{$suffix}";
        $patientId = 'TEST-'.now()->format('YmdHis');

        try {
            if (! mkdir($directory, 0700) && ! is_dir($directory)) {
                throw new \RuntimeException('Temporäres Verzeichnis konnte nicht erstellt werden.');
            }
            $generator = new Process([
                '/opt/registry-dicom/bin/python', base_path('scripts/dicom_storage_test_object.py'),
            ]);
            $generator->setInput(json_encode([
                'output_path' => $dicomPath,
                'sop_class' => $sopClass->value,
                'study_instance_uid' => $studyUid,
                'series_instance_uid' => $seriesUid,
                'sop_instance_uid' => $instanceUid,
                'frame_of_reference_uid' => $frameOfReferenceUid,
                'patient_id' => $patientId,
            ], JSON_THROW_ON_ERROR));
            $generator->setTimeout(10);
            $generator->run();

            if (! $generator->isSuccessful() || ! is_file($dicomPath)) {
                return new StorageCommandResult(false, $generator->getExitCode() ?? 1, trim($generator->getErrorOutput()), $instanceUid, generationFailed: true);
            }

            $tlsProfile = $this->tlsProfiles->resolve($node);

            $sender = new Process([
                '/usr/bin/storescu', '-v', '+v', '-rsp', '-to', '5', '-ts', '15', '-ta', '5', '-td', '15',
                '-aet', $callingAeTitle, '-aec', $calledAeTitle,
                ...($tlsProfile?->dcmtkArguments() ?? []),
                $node->host, (string) $node->port, $dicomPath,
            ]);
            $sender->setTimeout(25);
            $sender->run();

            return new StorageCommandResult(
                $sender->isSuccessful(), $sender->getExitCode() ?? 1,
                trim($sender->getOutput()."\n".$sender->getErrorOutput()), $instanceUid,
            );
        } catch (DicomTlsConfigurationException $exception) {
            return new StorageCommandResult(false, 1, $exception->getMessage(), $instanceUid, tlsConfigurationFailed: true);
        } catch (ProcessTimedOutException $exception) {
            return new StorageCommandResult(false, 1, $exception->getMessage(), $instanceUid, timedOut: true);
        } catch (Throwable $exception) {
            return new StorageCommandResult(false, 1, $exception->getMessage(), $instanceUid, generationFailed: true);
        } finally {
            foreach ([$dicomPath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
