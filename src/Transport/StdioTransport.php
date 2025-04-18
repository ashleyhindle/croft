<?php

declare(strict_types=1);

namespace Croft\Transport;

use Croft\Exceptions\TransportException;

class StdioTransport implements TransportInterface
{
    /** @var resource */
    private $stdin;

    /** @var resource */
    private $stdout;

    /**
     * Create a new StdioTransport
     *
     * Connects to standard input/output streams for JSON-RPC communication
     */
    public function __construct()
    {
        $this->stdin = fopen('php://stdin', 'r');
        $this->stdout = fopen('php://stdout', 'w');

        if (! $this->stdin || ! $this->stdout) {
            throw new TransportException('Failed to open stdio streams');
        }

        // Ensure streams are set to non-blocking mode
        stream_set_blocking($this->stdin, false);
    }

    /**
     * Read a message from stdin
     *
     * @return string|null The message or null if no message is available
     */
    public function read(): ?string
    {
        $line = fgets($this->stdin);

        if ($line === false) {
            // No data available (non-blocking)
            return null;
        }

        // Trim any whitespace/newlines
        $line = trim($line);

        // Return null for empty lines
        if (empty($line)) {
            return null;
        }

        return $line;
    }

    /**
     * Write a message to stdout
     *
     * @param  string  $data  The message to write
     * @return int The number of bytes written
     */
    public function write(string $data): int
    {
        $bytes = fwrite($this->stdout, $data.PHP_EOL);
        fflush($this->stdout);

        return $bytes;
    }

    /**
     * Close the transport
     */
    public function close(): void
    {
        if (is_resource($this->stdin)) {
            fclose($this->stdin);
        }

        if (is_resource($this->stdout)) {
            fclose($this->stdout);
        }
    }

    /**
     * Destructor ensures resources are properly closed
     */
    public function __destruct()
    {
        $this->close();
    }
}
