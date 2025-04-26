<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\Config;

class GetCurrentDateAndTime extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Get Current Date and Time')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'get_current_date_and_time';
    }

    public function getDescription(): string
    {
        return 'Get the current date and time';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
            ],
            'required' => [],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $now = now('UTC');
        return ToolResponse::array([
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i:s'),
            'timezone' => $now->getTimezone()->getName(),
            'iso8601' => $now->toIso8601String(),
        ]);
    }
}
