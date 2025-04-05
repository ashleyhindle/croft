<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\Config;

class ListAvailableConfigKeys extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('List Available Config Keys')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'list_available_config_keys';
    }

    public function getDescription(): string
    {
        return 'List all available config keys (from config/*.php) in dot notation';
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
        $configKeys = Config::all();
        $dotKeys = $this->flattenToDotNotation($configKeys);
        sort($dotKeys);

        return ToolResponse::array($dotKeys);
    }

    private function flattenToDotNotation(array $array, string $prepend = ''): array
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge(
                    $results,
                    $this->flattenToDotNotation($value, $prepend.$key.'.')
                );
            } else {
                $results[] = $prepend.$key;
            }
        }

        return $results;
    }
}
