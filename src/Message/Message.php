<?php

declare(strict_types=1);

namespace Croft\Message;

abstract class Message
{
    public const JSON_RPC_VERSION = '2.0';

    protected function __construct()
    {
        // No parameters needed as we're hardcoding the version
    }

    public function jsonSerialize(): array
    {
        return [
            'jsonrpc' => self::JSON_RPC_VERSION,
        ];
    }

    public static function validateJsonRpcVersion(string $version): void
    {
        if ($version !== self::JSON_RPC_VERSION) {
            throw new \InvalidArgumentException("Invalid JSON-RPC version: {$version}");
        }
    }
}
