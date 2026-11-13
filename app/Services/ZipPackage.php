<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Minimal zip reader/writer for Office files (.docx/.xlsx), built on zlib only so it works where the zip
 * extension is off (XAMPP ships with it commented out). Supports stored and deflated entries without zip64,
 * which covers the office form templates.
 *
 * Entries keep their order; unchanged entries are copied without recompressing.
 */
final class ZipPackage
{
    /** @var array<string, array{method: int, crc: int, csize: int, usize: int, time: int, date: int, data: string}> */
    private array $entries = [];

    public static function fromFile(string $path): self
    {
        $bytes = @file_get_contents($path);
        if (!is_string($bytes)) {
            throw new RuntimeException('Cannot read ' . basename($path));
        }

        return self::fromString($bytes, basename($path));
    }

    public static function fromString(string $bytes, string $label = 'archive'): self
    {
        $self = new self();
        $eocd = strrpos($bytes, "PK\x05\x06");
        if ($eocd === false || strlen($bytes) < $eocd + 22) {
            throw new RuntimeException($label . ' is not a valid Office file.');
        }
        $end = unpack('vdisk/vcdDisk/vdiskEntries/ventries/Vsize/Voffset', substr($bytes, $eocd + 4, 16));
        $pos = (int) $end['offset'];
        for ($i = 0; $i < (int) $end['entries']; $i++) {
            if (substr($bytes, $pos, 4) !== "PK\x01\x02") {
                throw new RuntimeException($label . ' has a damaged file list.');
            }
            $h = unpack('vmade/vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcsize/Vusize/vnameLen/vextraLen/vcommentLen/vdisk/vintAttr/VextAttr/Voffset', substr($bytes, $pos + 4, 42));
            $name = substr($bytes, $pos + 46, (int) $h['nameLen']);
            $pos += 46 + (int) $h['nameLen'] + (int) $h['extraLen'] + (int) $h['commentLen'];

            $local = (int) $h['offset'];
            $lh = unpack('vnameLen/vextraLen', substr($bytes, $local + 26, 4));
            $dataStart = $local + 30 + (int) $lh['nameLen'] + (int) $lh['extraLen'];
            $self->entries[$name] = [
                'method' => (int) $h['method'],
                'crc' => (int) $h['crc'],
                'csize' => (int) $h['csize'],
                'usize' => (int) $h['usize'],
                'time' => (int) $h['time'],
                'date' => (int) $h['date'],
                'data' => substr($bytes, $dataStart, (int) $h['csize']),
            ];
        }

        return $self;
    }

    public function get(string $name): ?string
    {
        $entry = $this->entries[$name] ?? null;
        if ($entry === null) {
            return null;
        }
        if ($entry['method'] === 0) {
            return $entry['data'];
        }
        if ($entry['method'] === 8) {
            $out = @gzinflate($entry['data']);
            return is_string($out) ? $out : null;
        }

        return null;
    }

    public function set(string $name, string $contents): void
    {
        $deflated = gzdeflate($contents, 6);
        $existing = $this->entries[$name] ?? null;
        $this->entries[$name] = [
            'method' => 8,
            'crc' => crc32($contents),
            'csize' => strlen((string) $deflated),
            'usize' => strlen($contents),
            'time' => $existing['time'] ?? 0,
            'date' => $existing['date'] ?? 0x21, // 1980-01-01
            'data' => (string) $deflated,
        ];
    }

    public function toString(): string
    {
        $body = '';
        $directory = '';
        foreach ($this->entries as $name => $e) {
            $offset = strlen($body);
            $common = pack('vvvvvVVVv', 20, 0, $e['method'], $e['time'], $e['date'], $e['crc'], $e['csize'], $e['usize'], strlen($name));
            // $common = need, flags, method, time, date, crc, csize, usize, nameLen
            $body .= "PK\x03\x04" . $common . pack('v', 0) . $name . $e['data'];
            $directory .= "PK\x01\x02" . pack('v', 20) . $common . pack('vvvvVV', 0, 0, 0, 0, 0, $offset) . $name;
        }
        $count = count($this->entries);

        return $body . $directory . "PK\x05\x06" . pack('vvvvVVv', 0, 0, $count, $count, strlen($directory), strlen($body), 0);
    }
}
