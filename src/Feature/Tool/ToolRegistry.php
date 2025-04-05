<?php

declare(strict_types=1);

namespace Croft\Feature\Tool;

use Croft\Exceptions\ValidationException;
use Croft\Feature\AbstractRegistry;

/**
 * Registry for MCP tools
 */
class ToolRegistry extends AbstractRegistry
{
    /**
     * @var array<string, AbstractTool> Map of tool name to tool instance
     */
    protected array $items = [];

    protected function validateItem(object $item): bool
    {
        return $item instanceof AbstractTool;
    }

    /**
     * Get schemas for all registered tools
     *
     * @return array Tool schemas in the format required by the MCP protocol
     */
    public function getSchemas(): array
    {
        $schemas = [];

        foreach ($this->items as $name => $tool) {
            $schemas[] = [
                'name' => $name,
                'description' => $tool->getDescription(),
                'inputSchema' => $tool->getInputSchema(),
                'annotations' => $tool->getAnnotations(),
            ];
        }

        return $schemas;
    }

    /**
     * Get the number of registered tools
     *
     * @return int The number of tools
     */
    public function count(): int
    {
        return count($this->items);
    }
}
