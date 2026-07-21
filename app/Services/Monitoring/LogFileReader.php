<?php

namespace App\Services\Monitoring;

class LogFileReader
{
    private const MAX_LIMIT = 500;
    private const CHUNK_SIZE = 65536; // 64 KB

    protected string $path;

    public function __construct()
    {
        $this->path = storage_path('logs/laravel.log');
    }

    /**
     * Return the $limit most recent log entries as raw strings.
     *
     * Reads backward in chunks via byte-seek (O(bytes read) not O(total lines))
     * to avoid scanning the full file when the log grows large.
     */
    public function tail(int $limit = 100): array
    {
        $limit = min($limit, self::MAX_LIMIT);

        if (! file_exists($this->path) || ! is_readable($this->path)) {
            return [];
        }

        $fp = fopen($this->path, 'rb');
        if (! $fp) {
            return [];
        }

        fseek($fp, 0, SEEK_END);
        $fileSize = ftell($fp);

        if ($fileSize === 0) {
            fclose($fp);
            return [];
        }

        $entries      = [];
        $currentEntry = [];
        $tail         = ''; // partial line fragment from the right edge of the previous chunk
        $pos          = $fileSize;

        while ($pos > 0 && count($entries) < $limit) {
            $readSize = min(self::CHUNK_SIZE, $pos);
            $pos -= $readSize;

            fseek($fp, $pos);
            // Prepend new chunk to any partial line we held from the previous iteration
            $text  = fread($fp, $readSize) . $tail;
            $lines = preg_split("/\r\n|\n|\r/", $text);

            // First element may be incomplete (cut at the left chunk boundary)
            $tail = array_shift($lines);

            // Walk lines from right to left — we are reading backward
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = rtrim($lines[$i]);
                array_unshift($currentEntry, $line);

                if (preg_match('/^\[\d{4}-\d{2}-\d{2}/', $line)) {
                    $entries[] = trim(implode("\n", $currentEntry));
                    $currentEntry = [];

                    if (count($entries) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        // Flush the entry that begins at the very start of the file
        if (count($entries) < $limit) {
            if ($tail !== '') {
                array_unshift($currentEntry, rtrim($tail));
            }
            if (! empty($currentEntry)) {
                $entries[] = trim(implode("\n", $currentEntry));
            }
        }

        fclose($fp);

        return array_reverse($entries);
    }
}
