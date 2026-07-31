<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class NativeStorageCommandRunner implements StorageCommandRunner
{
    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): StorageCommandResult
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-store-'.Str::random(20);
        $imagePath = $directory.DIRECTORY_SEPARATOR.'test.bmp';
        $dicomPath = $directory.DIRECTORY_SEPARATOR.'test.dcm';
        $uidRoot = '1.2.826.0.1.3680043.10.987';
        $suffix = now()->format('YmdHis').random_int(100000, 999999);
        $studyUid = "{$uidRoot}.1.{$suffix}";
        $seriesUid = "{$uidRoot}.2.{$suffix}";
        $instanceUid = "{$uidRoot}.3.{$suffix}";
        $patientId = 'TEST-'.now()->format('YmdHis');

        try {
            if (! mkdir($directory, 0700) && ! is_dir($directory)) {
                throw new \RuntimeException('Temporäres Verzeichnis konnte nicht erstellt werden.');
            }
            file_put_contents($imagePath, $this->bitmap());
            $generator = new Process([
                '/usr/bin/img2dcm', '-i', 'BMP', '-sc',
                '-k', 'PatientName=DICOMNODE^TEST', '-k', "PatientID={$patientId}",
                '-k', 'StudyDescription=DICOM Connectivity Test', '-k', "StudyInstanceUID={$studyUid}",
                '-k', "SeriesInstanceUID={$seriesUid}", '-k', "SOPInstanceUID={$instanceUid}",
                $imagePath, $dicomPath,
            ]);
            $generator->setTimeout(10);
            $generator->run();

            if (! $generator->isSuccessful() || ! is_file($dicomPath)) {
                return new StorageCommandResult(false, $generator->getExitCode() ?? 1, trim($generator->getErrorOutput()), $instanceUid, generationFailed: true);
            }

            $sender = new Process([
                '/usr/bin/storescu', '-v', '+v', '-rsp', '-to', '5', '-ts', '15', '-ta', '5', '-td', '15',
                '-aet', $callingAeTitle, '-aec', $calledAeTitle,
                $node->host, (string) $node->port, $dicomPath,
            ]);
            $sender->setTimeout(25);
            $sender->run();

            return new StorageCommandResult(
                $sender->isSuccessful(), $sender->getExitCode() ?? 1,
                trim($sender->getOutput()."\n".$sender->getErrorOutput()), $instanceUid,
            );
        } catch (ProcessTimedOutException $exception) {
            return new StorageCommandResult(false, 1, $exception->getMessage(), $instanceUid, timedOut: true);
        } catch (Throwable $exception) {
            return new StorageCommandResult(false, 1, $exception->getMessage(), $instanceUid, generationFailed: true);
        } finally {
            foreach ([$dicomPath, $imagePath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    private function bitmap(): string
    {
        $pixelData = "\x00\x00\x00\x00";

        return 'BM'.pack('VvvV', 58, 0, 0, 54)
            .pack('V3v2V6', 40, 1, 1, 1, 24, 0, 4, 2835, 2835, 0, 0)
            .$pixelData;
    }
}
