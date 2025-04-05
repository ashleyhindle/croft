<?php

declare(strict_types=1);

namespace Croft\Feature\Prompt;

use Croft\Feature\AbstractRegistry;

/**
 * Registry for MCP prompts
 *
 * Manages registration and lookup of prompt implementations
 */
class PromptRegistry extends AbstractRegistry
{
    /** @var array<string, AbstractPrompt> */
    protected array $items = [];

    protected function validateItem(object $item): bool
    {
        return $item instanceof AbstractPrompt;
    }

    /**
     * Get schemas for all registered items
     *
     * @return array List of prompt schemas
     */
    public function getSchemas(): array
    {
        $schemas = [];

        foreach ($this->items as $name => $prompt) {
            $promptSchema = [
                'name' => $name,
                'description' => $prompt->getDescription(),
            ];

            $argumentSchema = $prompt->getSchema();
            if (! empty($argumentSchema['properties'])) {
                $arguments = [];
                $required = $argumentSchema['required'] ?? [];

                foreach ($argumentSchema['properties'] as $argName => $argProps) {
                    $arguments[] = [
                        'name' => $argName,
                        'description' => $argProps['description'] ?? '',
                        'required' => in_array($argName, $required),
                    ];
                }

                if (! empty($arguments)) {
                    $promptSchema['arguments'] = $arguments;
                }
            }

            $schemas[] = $promptSchema;
        }

        return $schemas;
    }

    /**
     * Get the number of registered prompts
     *
     * @return int The number of prompts
     */
    public function count(): int
    {
        return count($this->items);
    }
}
