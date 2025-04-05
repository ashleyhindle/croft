<?php

declare(strict_types=1);

namespace Croft\Feature\Resource;

use Croft\Exceptions\ProtocolException;
use Croft\Feature\AbstractRegistry;
use Croft\Protocol\JsonRpc;

/**
 * Registry for MCP resource templates
 *
 * Manages registration and lookup of resource template implementations
 */
class ResourceTemplateRegistry extends AbstractRegistry
{
    /** @var array<string, AbstractResourceTemplate> */
    protected array $items = [];

    protected function validateItem(object $item): bool
    {
        return $item instanceof AbstractResourceTemplate;
    }

    /**
     * Get a template by URI template
     *
     * @param  string  $uriTemplate  The URI template pattern
     * @return AbstractResourceTemplate|null The template instance, or null if not found
     */
    public function getItem(string $uriTemplate): ?object
    {
        return $this->items[$uriTemplate] ?? null;
    }

    public function getSchemas(): array
    {
        $schemas = [];

        foreach ($this->items as $template) {
            $schemas[] = $template->toArray();
        }

        return $schemas;
    }

    /**
     * Get a template by URI template
     *
     * @param  string  $uriTemplate  The URI template pattern
     * @return AbstractResourceTemplate The template
     *
     * @throws ProtocolException If the template is not found
     */
    public function get(string $uriTemplate): AbstractResourceTemplate
    {
        if (! isset($this->items[$uriTemplate])) {
            throw new ProtocolException(
                sprintf('Resource template not found: %s', $uriTemplate),
                JsonRpc::RESOURCE_NOT_FOUND
            );
        }

        return $this->items[$uriTemplate];
    }
}
