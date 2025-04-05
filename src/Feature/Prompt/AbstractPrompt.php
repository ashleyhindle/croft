<?php

declare(strict_types=1);

namespace Croft\Feature\Prompt;

/**
 * Base class for all MCP prompts
 *
 * Prompts provide templated messages and workflows for LLM interactions
 */
abstract class AbstractPrompt
{
    /**
     * Get the unique name of this prompt
     *
     * @return string The prompt name
     */
    abstract public function getName(): string;

    /**
     * Get the human-readable description of this prompt
     *
     * @return string The prompt description
     */
    abstract public function getDescription(): string;

    /**
     * Get the argument schema for this prompt
     *
     * This defines what parameters can be passed when using the prompt
     *
     * @return array The JSON Schema for prompt arguments
     */
    abstract public function getSchema(): array;

    /**
     * Render the prompt with the given arguments
     *
     * @param  array  $arguments  The arguments to use for rendering
     * @return array The rendered prompt content
     */
    abstract public function render(array $arguments): array;
}
