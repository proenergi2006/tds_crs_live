<?php

namespace App\Services\Monitoring;

class LogFileReader
{
    private const CHUNK_SIZE = 65536; // 64 KB
    private const FILENAME_PATTERN = '/^laravel(-\d{4}-\d{2}-\d{2})?\.log$/';

    protected string $logsPath;

    public function __construct()
    {
        $this->logsPath = storage_path('logs');
    }

    /**
     * List every laravel*.log file inside storage/logs, newest first.
     */
    public function listFiles(): array
    {
        $files = glob($this->logsPath . DIRECTORY_SEPARATOR . 'laravel*.log') ?: [];

        $result = [];

        foreach ($files as $file) {
            $filename = basename($file);

            if (! preg_match(self::FILENAME_PATTERN, $filename)) {
                continue;
            }

            $result[] = [
                'filename' => $filename,
                'size' => filesize($file),
                'modified_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        usort($result, fn(array $a, array $b) => strcmp($b['modified_at'], $a['modified_at']));

        return $result;
    }

    /**
     * Return every log entry in $filename as raw strings, oldest first.
     *
     * Reads backward in chunks via byte-seek (O(bytes read) not O(total lines))
     * to avoid loading the whole file into memory at once.
     */
    public function read(string $filename): array
    {
        if (! preg_match(self::FILENAME_PATTERN, $filename)) {
            return [];
        }

        $path = $this->logsPath . DIRECTORY_SEPARATOR . $filename;

        if (! file_exists($path) || ! is_readable($path)) {
            return [];
        }

        $fp = fopen($path, 'rb');
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

        while ($pos > 0) {
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
                }
            }
        }

        // Flush the entry that begins at the very start of the file
        if ($tail !== '') {
            array_unshift($currentEntry, rtrim($tail));
        }
        if (! empty($currentEntry)) {
            $entries[] = trim(implode("\n", $currentEntry));
        }

        fclose($fp);

        return array_reverse($entries);
    }
}
