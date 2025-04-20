<?php

declare(strict_types=1);

namespace Croft\Protocol;

use Croft\Exceptions\ProtocolException;
use Croft\Message\Message;
use Croft\Message\Notification;
use Croft\Message\Request;
use Croft\Message\Response;

/**
 * Utility class for handling JSON-RPC 2.0 protocol messages
 */
class JsonRpc
{
    // Standard JSON-RPC 2.0 error codes
    public const PARSE_ERROR = -32700;

    public const INVALID_REQUEST = -32600;

    public const METHOD_NOT_FOUND = -32601;

    public const INVALID_PARAMS = -32602;

    public const INTERNAL_ERROR = -32603;

    // MCP-specific error codes
    public const RESOURCE_NOT_FOUND = -32002;

    /**
     * Parse a raw JSON-RPC message string
     *
     * @param  string  $message  The raw JSON-RPC message
     * @return Request|Response|Notification The parsed message object
     *
     * @throws ProtocolException If the message is invalid
     */
    public static function parse(string $message): Request|Response|Notification
    {
        $data = json_decode($message, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ProtocolException('Invalid JSON: '.json_last_error_msg(), self::PARSE_ERROR);
        }

        // Validate it's a proper JSON-RPC message
        if (! is_array($data) || ! isset($data['jsonrpc']) || $data['jsonrpc'] !== Message::JSON_RPC_VERSION) {
            throw new ProtocolException('Invalid JSON-RPC message', self::INVALID_REQUEST);
        }

        // Determine message type
        if (isset($data['method'])) {
            if (isset($data['id'])) {
                return Request::fromArray($data);
            } else {
                return Notification::fromArray($data);
            }
        } elseif (isset($data['id']) && (array_key_exists('result', $data) || array_key_exists('error', $data))) {
            return Response::fromArray($data);
        }

        throw new ProtocolException('Invalid JSON-RPC message structure', self::INVALID_REQUEST);
    }

    /**
     * Convert a message object to JSON
     *
     * @param  Request|Response|Notification  $message  The message object
     * @return string The JSON-encoded message
     */
    public static function stringify(Request|Response|Notification $message): string
    {
        return json_encode($message);
    }

    /**
     * Create a standard error response for a request
     *
     * @param  string|int  $id  The request ID
     * @param  int  $code  The error code
     * @param  string  $message  The error message
     * @param  mixed  $data  Additional error data
     * @return Response The error response
     */
    public static function error(string|int $id, int $code, string $message, mixed $data = null): Response
    {
        return Response::error($id, $code, $message, $data);
    }
}
