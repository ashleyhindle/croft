<?php

declare(strict_types=1);

namespace Croft\Feature\Resource;

use Croft\Exceptions\ResourceNotFoundException;
use Croft\Feature\AbstractRegistry;

/**
 * Registry for MCP resources
 *
 * Manages registration and lookup of resource implementations
 */
class ResourceRegistry extends AbstractRegistry
{
    /** @var array<string, AbstractResource> */
    protected array $items = [];

    protected function validateItem(object $item): bool
    {
        return $item instanceof AbstractResource;
    }

    /**
     * Get a resource by URI
     *
     * @param  string  $uri  The URI of the resource to retrieve
     * @return AbstractResource The resource
     *
     * @throws ResourceNotFoundException If the resource is not found
     */
    public function get(string $uri): AbstractResource
    {
        if (! isset($this->items[$uri])) {
            throw new ResourceNotFoundException(sprintf('Resource not found: %s', $uri));
        }

        return $this->items[$uri];
    }

    /**
     * Get schemas for all registered items
     *
     * @return array List of resource schemas
     */
    public function getSchemas(): array
    {
        $schemas = [];

        foreach ($this->items as $uri => $resource) {
            $schemas[] = [
                'uri' => $uri,
                'name' => $resource->getName(),
                'description' => $resource->getDescription(),
                'mimeType' => $resource->getMimeType(),
            ];
        }

        return $schemas;
    }
}
