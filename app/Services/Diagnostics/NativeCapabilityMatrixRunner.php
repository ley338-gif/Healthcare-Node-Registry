<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

final class NativeCapabilityMatrixRunner implements CapabilityMatrixRunner
{
    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle, array $contexts): CapabilityMatrixResult
    {
        $errorNumber = 0;
        $errorMessage = '';
        $socket = @stream_socket_client("tcp://{$node->host}:{$node->port}", $errorNumber, $errorMessage, 5);
        if ($socket === false) {
            return new CapabilityMatrixResult(false, [], $errorMessage ?: "Netzwerkfehler {$errorNumber}", str_contains(strtolower($errorMessage), 'timed out'));
        }

        stream_set_timeout($socket, 10);
        fwrite($socket, $this->associateRequest($callingAeTitle, $calledAeTitle, $contexts));
        $header = $this->read($socket, 6);
        if (strlen($header) !== 6) {
            $metadata = stream_get_meta_data($socket);
            fclose($socket);

            return new CapabilityMatrixResult(false, [], 'Keine vollständige DICOM-Association-Antwort.', $metadata['timed_out']);
        }
        $type = ord($header[0]);
        $length = unpack('Nlength', substr($header, 2, 4))['length'];
        $body = $this->read($socket, $length);

        if ($type !== 0x02) {
            fclose($socket);

            return new CapabilityMatrixResult(false, [], $type === 0x03 ? 'DICOM Association wurde abgelehnt.' : 'Unerwartete DICOM-Association-Antwort.');
        }

        $results = $this->parseContextResults($body);
        fwrite($socket, "\x05\0\0\0\0\x04\0\0\0\0");
        $this->read($socket, 10);
        fclose($socket);

        return new CapabilityMatrixResult(true, $results);
    }

    /** @param list<array{id: int, sopClassUid: string, transferSyntaxUid: string}> $contexts */
    private function associateRequest(string $calling, string $called, array $contexts): string
    {
        $items = $this->item(0x10, '1.2.840.10008.3.1.1.1');
        foreach ($contexts as $context) {
            $abstract = $this->item(0x30, $context['sopClassUid']);
            $syntax = $this->item(0x40, $context['transferSyntaxUid']);
            $items .= $this->item(0x20, chr($context['id'])."\0\0\0".$abstract.$syntax);
        }
        $userInfo = $this->item(0x51, pack('N', 16384));
        $items .= $this->item(0x50, $userInfo);
        $body = pack('n', 1)."\0\0".str_pad(substr($called, 0, 16), 16).str_pad(substr($calling, 0, 16), 16).str_repeat("\0", 32).$items;

        return "\x01\0".pack('N', strlen($body)).$body;
    }

    private function item(int $type, string $value): string
    {
        return chr($type)."\0".pack('n', strlen($value)).$value;
    }

    /** @return array<int, int> */
    private function parseContextResults(string $body): array
    {
        $results = [];
        $offset = 68;
        while ($offset + 4 <= strlen($body)) {
            $type = ord($body[$offset]);
            $length = unpack('nlength', substr($body, $offset + 2, 2))['length'];
            $value = substr($body, $offset + 4, $length);
            if ($type === 0x21 && strlen($value) >= 4) {
                $results[ord($value[0])] = ord($value[2]);
            }
            $offset += 4 + $length;
        }

        return $results;
    }

    /** @param resource $socket */
    private function read($socket, int $length): string
    {
        $data = '';
        while (strlen($data) < $length && ! feof($socket)) {
            $chunk = fread($socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $data .= $chunk;
        }

        return $data;
    }
}
