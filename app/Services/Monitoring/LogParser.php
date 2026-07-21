<?php

namespace App\Services\Monitoring;

class LogParser
{
    public function parseMany(array $entries): array
    {
        return array_map(
            fn(string $entry) => $this->parse($entry),
            $entries
        );
    }

    public function parse(string $entry): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $entry);
        $header = array_shift($lines);
        $pattern = '/^\[(.*?)\]\s+([^.]+)\.(\w+):\s?(.*)$/';

        $timestamp = null;
        $environment = null;
        $level = null;
        $message = '';
        $trace = '';

        if (preg_match($pattern, $header, $matches)) {
            $timestamp = $matches[1];
            $environment = $matches[2];
            $level = strtoupper($matches[3]);
            $message = $matches[4];
        }

        if (!empty($lines)) {
            $trace = implode("\n", $lines);
        }

        return [
            'id' => sha1($entry),
            'timestamp' => $timestamp,
            'environment' => $environment,
            'level' => $level,
            'message' => $message,
            'trace' => $trace,
            'raw' => $entry,
        ];
    }
}
