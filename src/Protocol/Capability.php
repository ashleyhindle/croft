<?php

declare(strict_types=1);

namespace Croft\Protocol;

use Croft\Feature\Prompt\PromptRegistry;
use Croft\Feature\Resource\ResourceRegistry;
use Croft\Feature\Tool\ToolRegistry;

/**
 * Utility class for handling MCP capability negotiation
 */
class Capability
{
    /**
     * Current MCP protocol version
     */
    public const PROTOCOL_VERSION = '2024-11-05';

    /**
     * Create server capabilities for initialization
     *
     * @param  array  $capabilities  Server capabilities
     * @return array The server capabilities
     */
    public static function createServerCapabilities(array $capabilities = []): array
    {
        // The server should provide a complete capability list
        return $capabilities;
    }

    /**
     * Create a server info object
     *
     * @param  string  $name  Server name
     * @param  string  $version  Server version
     * @return array The server info
     */
    public static function createServerInfo(string $name, string $version): array
    {
        return [
            'name' => $name,
            'version' => $version,
        ];
    }

    /**
     * Create an initialization response
     *
     * @param  string  $name  Server name
     * @param  string  $version  Server version
     * @param  array  $capabilities  Server capabilities
     * @return array The initialization response
     */
    public static function createInitializeResponse(
        string $name,
        string $version,
        array $capabilities = [],
        string $instructions = ''
    ): array {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => self::createServerCapabilities($capabilities),
            'serverInfo' => self::createServerInfo($name, $version),
            'instructions' => $instructions,
        ];
    }

    /**
     * Negotiate server capabilities based on client capabilities and server registries
     *
     * @param  array  $clientCapabilities  Client capabilities from initialize request
     * @param  ToolRegistry  $toolRegistry  Server's tool registry
     * @param  PromptRegistry  $promptRegistry  Server's prompt registry
     * @param  ResourceRegistry  $resourceRegistry  Server's resource registry
     * @return array Negotiated server capabilities
     */
    public static function negotiateCapabilities(
        array $clientCapabilities,
        ToolRegistry $toolRegistry,
        PromptRegistry $promptRegistry,
        ResourceRegistry $resourceRegistry
    ): array {
        $serverCapabilities = [];

        // Check if client explicitly disabled features
        $clientSupportsTools = ! (isset($clientCapabilities['tools']) && $clientCapabilities['tools'] === false);
        $clientSupportsPrompts = ! (isset($clientCapabilities['prompts']) && $clientCapabilities['prompts'] === false);
        $clientSupportsResources = ! (isset($clientCapabilities['resources']) && $clientCapabilities['resources'] === false);
        $clientSupportsLogging = ! (isset($clientCapabilities['logging']) && $clientCapabilities['logging'] === false);

        // Determine if we have corresponding features
        $hasTools = $toolRegistry->count() > 0;
        $hasPrompts = $promptRegistry->count() > 0;
        $hasResources = $resourceRegistry->count() > 0;

        // Add tools capability if we have tools and client supports them
        if ($hasTools && $clientSupportsTools) {
            $serverCapabilities['tools'] = self::negotiateToolsCapabilities($clientCapabilities);
        }

        // Add prompts capability if we have prompts and client supports them
        if ($hasPrompts && $clientSupportsPrompts) {
            $serverCapabilities['prompts'] = self::negotiatePromptsCapabilities($clientCapabilities);
        }

        // Add resources capability if we have resources and client supports them
        if ($hasResources && $clientSupportsResources) {
            $serverCapabilities['resources'] = self::negotiateResourcesCapabilities($clientCapabilities);
        }

        // We always provide logging capability unless client explicitly disables it
        if ($clientSupportsLogging) {
            $serverCapabilities['logging'] = new \stdClass();
        }

        return $serverCapabilities;
    }

    /**
     * Negotiate tools capabilities based on client capabilities
     *
     * @param  array  $clientCapabilities  Client capabilities from initialize request
     * @return array Negotiated tools capabilities
     */
    private static function negotiateToolsCapabilities(array $clientCapabilities): array
    {
        return [
            // Only advertise listChanged if client can handle notifications
            'listChanged' => isset($clientCapabilities['tools']['listChanged']) &&
                $clientCapabilities['tools']['listChanged'] === true,
        ];
    }

    /**
     * Negotiate prompts capabilities based on client capabilities
     *
     * @param  array  $clientCapabilities  Client capabilities from initialize request
     * @return array Negotiated prompts capabilities
     */
    private static function negotiatePromptsCapabilities(array $clientCapabilities): array
    {
        return [
            // Only advertise listChanged if client can handle notifications
            'listChanged' => isset($clientCapabilities['prompts']['listChanged']) &&
                $clientCapabilities['prompts']['listChanged'] === true,
        ];
    }

    /**
     * Negotiate resources capabilities based on client capabilities
     *
     * @param  array  $clientCapabilities  Client capabilities from initialize request
     * @return array Negotiated resources capabilities
     */
    private static function negotiateResourcesCapabilities(array $clientCapabilities): array
    {
        return [
            // Only advertise listChanged if client can handle notifications
            'listChanged' => isset($clientCapabilities['resources']['listChanged']) &&
                $clientCapabilities['resources']['listChanged'] === true,
            // We don't support subscriptions yet
            'subscribe' => false,
        ];
    }
}
