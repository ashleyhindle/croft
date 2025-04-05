<?php

declare(strict_types=1);

namespace Croft\Exceptions;

use Croft\Protocol\JsonRpc;

/**
 * Exception thrown when a requested resource is not found
 */
class ResourceNotFoundException extends ProtocolException
{
    public function __construct(string $message, int $code = JsonRpc::RESOURCE_NOT_FOUND)
    {
        parent::__construct($message, $code);
    }
}
