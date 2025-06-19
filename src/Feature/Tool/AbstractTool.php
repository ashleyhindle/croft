<?php

declare(strict_types=1);

namespace Croft\Feature\Tool;

use Croft\Cache;

/**
 * AbstractTool is the base class for all MCP tool implementations.
 */
abstract class AbstractTool
{
    protected array $annotations = [];

    protected Cache $cache;

    public function setCache(Cache $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Conditionally register the tool with the client.
     * For example, if they don't have 'browsershot' installed, don't register a tool requiring it.
     */
    public function shouldRegister(): bool
    {
        return true;
    }

    /**
     * Get the name of the tool.
     * This is the identifier used when registering with the server.
     *
     * @return string The tool name
     */
    abstract public function getName(): string;

    /**
     * Get the description of the tool.
     * This helps clients understand what the tool does.
     *
     * @return string The tool description
     */
    public function getDescription(): string
    {
        return ''; // Default to empty description, subclasses can override
    }

    /**
     * Get the JSON schema for tool arguments.
     * This schema defines the expected input arguments for the tool.
     *
     * @return array The schema in JSON Schema format
     */
    abstract public function getInputSchema(): array;

    /**
     * Get the tool annotations.
     * These are optional properties that describe tool behavior.
     *
     * @return array The tool annotations
     */
    public function getAnnotations(): array
    {
        return array_merge([
            'destructiveHint' => true,  // Default per spec
            'idempotentHint' => false,  // Default per spec
            'openWorldHint' => true,    // Default per spec
            'readOnlyHint' => false,    // Default per spec
            'title' => $this->getName(), // Use name as default title
        ], $this->annotations);
    }

    /**
     * Set whether the tool performs destructive updates
     */
    protected function setDestructive(bool $value = true): self
    {
        $this->annotations['destructiveHint'] = $value;

        return $this;
    }

    /**
     * Set whether the tool is idempotent
     */
    protected function setIdempotent(bool $value = true): self
    {
        $this->annotations['idempotentHint'] = $value;

        return $this;
    }

    /**
     * Set whether the tool interacts with an "open world"
     */
    protected function setOpenWorld(bool $value = true): self
    {
        $this->annotations['openWorldHint'] = $value;

        return $this;
    }

    /**
     * Set whether the tool is read-only
     */
    protected function setReadOnly(bool $value = true): self
    {
        $this->annotations['readOnlyHint'] = $value;

        return $this;
    }

    /**
     * Set a human-readable title for the tool
     */
    protected function setTitle(string $title): self
    {
        $this->annotations['title'] = $title;

        return $this;
    }

    /**
     * Handle a tool invocation with the provided arguments.
     * This method is called when the tool is invoked by the MCP client.
     *
     * @param  array  $arguments  The arguments provided by the client
     * @return ToolResponse The result of the tool invocation
     */
    abstract public function handle(array $arguments): ToolResponse;

    /**
     * Convert the tool response to the expected array format
     * This ensures consistent response structure across all tools
     *
     * @param  ToolResponse  $response  The tool response
     * @return array The formatted response
     */
    protected function formatResponse(ToolResponse $response): array
    {
        return $response->toArray();
    }
}
