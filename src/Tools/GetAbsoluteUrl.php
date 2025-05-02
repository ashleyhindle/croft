<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;

class GetAbsoluteUrl extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Get Absolute URL for a given relative URL or route')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'get_absolute_url';
    }

    public function getDescription(): string
    {
        return 'Get the absolute URL for a given relative URL or route. Very useful for browser navigation and screenshotting.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'path' => [
                    'type' => 'string',
                    'description' => 'The relative URL to get the absolute URL for.',
                ],
                'route' => [
                    'type' => 'string',
                    'description' => 'The route to get the absolute URL for.',
                ],
            ],
            'required' => ['path', 'route'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $path = $arguments['path'];
        $route = $arguments['route'];

        if ($path) {
            return ToolResponse::array([
                'url' => url($path),
            ]);
        }

        if ($route) {
            return ToolResponse::array([
                'url' => route($route),
            ]);
        }

        return ToolResponse::array([
            'error' => 'No path or route provided',
        ]);
    }
}
