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
    private array $arguments = [];

    public function arguments(?array $arguments = null): array
    {
        if ($arguments) {
            $this->arguments = $arguments;
        }

        return $this->arguments;
    }

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

    abstract public function getMessage(array $arguments): string;

    public function getResponse(array $arguments): PromptResponse
    {
        $this->arguments($arguments);

        return PromptResponse::text($this);
    }
}
