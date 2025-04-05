<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\Config;

class ReadLogEntries extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Read Log Entries')
            ->setReadOnly(true)        // Reading logs doesn't modify anything
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Reading logs is safe to retry
            ->setOpenWorld(false);     // Log files are a closed world
    }

    public function getName(): string
    {
        return 'read_log_entries';
    }

    public function getDescription(): string
    {
        return 'Read the last X log entries from the log file, handling multi-line PSR-3 formatted logs.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entries' => [
                    'type' => 'integer',
                    'description' => 'Number of log entries to return. Each entry may span multiple lines.',
                ],
            ],
            'required' => ['entries'],
        ];
    }

    /**
     * Check if a line is the start of a new log entry based on PSR-3 format
     * PSR-3 logs typically start with a timestamp in brackets
     */
    private function isNewLogEntry(string $line): bool
    {
        // PSR-3 logs typically start with [YYYY-MM-DD HH:mm:ss]
        return preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line) === 1;
    }

    /**
     * Read lines from the end of a file
     * @return \Generator<string>
     */
    private function readLinesReverse($file): \Generator
    {
        // Start at end of file
        fseek($file, 0, SEEK_END);
        $pos = ftell($file);
        $line = '';

        // Read backwards one character at a time
        while ($pos >= 0) {
            // Move back one character
            $pos--;
            if ($pos >= 0) {
                fseek($file, $pos);
                $char = fgetc($file);
            } else {
                $char = "\n"; // Treat start of file as newline
            }

            // If we hit a newline, yield the accumulated line
            if ($char === "\n") {
                if ($line !== '') {
                    yield strrev($line);
                }
                $line = '';
            } else {
                $line .= $char;
            }
        }

        // Don't forget the first line if we have one
        if ($line !== '') {
            yield strrev($line);
        }
    }

    public function handle(array $arguments): ToolResponse
    {
        $maxEntries = $arguments['entries'];
        if ($maxEntries === 0) {
            return ToolResponse::error('Number of entries must be greater than 0');
        }

        $channel = Config::get('logging.default');
        $channelConfig = Config::get("logging.channels.{$channel}");

        if ($channelConfig['driver'] === 'daily') {
            $logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
        } else {
            $logFile = storage_path('logs/laravel.log');
        }

        if (!file_exists($logFile)) {
            return ToolResponse::error('Log file not found');
        }

        $file = fopen($logFile, 'r');
        if (!$file) {
            return ToolResponse::error('Could not open log file');
        }

        try {
            $logEntries = [];
            $currentEntry = [];
            $entryCount = 0;

            foreach ($this->readLinesReverse($file) as $line) {
                $line = rtrim($line);
                if (empty($line)) {
                    continue;
                }


                // Add line to current entry
                array_unshift($currentEntry, $line);

                // If this is a new entry and we have lines collected
                if ($this->isNewLogEntry($line) && !empty($currentEntry)) {
                    // Save the completed entry
                    array_unshift($logEntries, implode("\n", $currentEntry));
                    $entryCount++;

                    // Start new entry
                    $currentEntry = [];

                    // Check if we've hit our limit
                    if ($entryCount >= $maxEntries) {
                        break;
                    }
                }
            }

            // Add any remaining lines as the final entry
            if (!empty($currentEntry)) {
                array_unshift($logEntries, implode("\n", $currentEntry));
            }

            return ToolResponse::text(implode("\n\n", $logEntries));
        } finally {
            fclose($file);
        }
    }
}
