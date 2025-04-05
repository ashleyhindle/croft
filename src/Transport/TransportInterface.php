<?php

declare(strict_types=1);

namespace Croft\Transport;

interface TransportInterface
{
    /**
     * Read a message from the transport
     *
     * @return string|null The message or null if no message is available
     */
    public function read(): ?string;

    /**
     * Write a message to the transport
     *
     * @param  string  $data  The message to write
     * @return int The number of bytes written
     */
    public function write(string $data): int;

    /**
     * Close the transport
     */
    public function close(): void;
}
