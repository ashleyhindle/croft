<?php

declare(strict_types=1);

namespace Croft\Feature\Resource;

/**
 * Base class for all MCP resource templates
 *
 * Resource templates define patterns for parameterized resources
 */
abstract class AbstractResourceTemplate
{
    /**
     * Get the URI template pattern
     *
     * @return string The URI template (e.g., "file:///{path}")
     */
    abstract public function getUriTemplate(): string;

    /**
     * Get the name of the template
     *
     * @return string The human-readable name for display purposes
     */
    abstract public function getName(): string;

    /**
     * Get the description of the template
     *
     * @return string|null The description or null if none
     */
    public function getDescription(): ?string
    {
        return null;
    }

    /**
     * Get the default MIME type for resources from this template
     *
     * @return string|null The MIME type or null if unknown
     */
    public function getMimeType(): ?string
    {
        return null;
    }

    /**
     * Create a resource instance from a URI and extracted parameters
     *
     * @param  string  $uri  The original URI
     * @param  array  $params  Parameters extracted from the URI
     * @return AbstractResource The resource instance
     */
    abstract public function createResource(string $uri, array $params): AbstractResource;

    /**
     * Convert the template to an array representation for JSON responses
     *
     * @return array The template as an array
     */
    public function toArray(): array
    {
        $result = [
            'uriTemplate' => $this->getUriTemplate(),
            'name' => $this->getName(),
        ];

        if ($this->getDescription() !== null) {
            $result['description'] = $this->getDescription();
        }

        if ($this->getMimeType() !== null) {
            $result['mimeType'] = $this->getMimeType();
        }

        return $result;
    }
}
