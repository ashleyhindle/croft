<?php

declare(strict_types=1);

namespace Croft\Feature\Resource;

/**
 * Base class for all MCP resources
 *
 * Resources provide context and data for LLM interactions
 */
abstract class AbstractResource
{
    /**
     * Get the URI of the resource
     *
     * This URI MUST be unique across all resources registered with the server.
     * Resources are identified and accessed by their URI.
     *
     * @return string The unique URI that identifies this resource
     */
    abstract public function getUri(): string;

    /**
     * Get the name of the resource (used for display purposes)
     *
     * The name is primarily for human readability and display.
     * While not technically required to be unique, using unique names
     * improves the user experience.
     *
     * @return string The human-readable name of the resource
     */
    public function getName(): string
    {
        // Default implementation returns the last part of the URI
        $parts = explode('/', $this->getUri());

        return end($parts);
    }

    /**
     * Get an optional description of the resource
     *
     * @return string|null The description or null if none
     */
    public function getDescription(): ?string
    {
        return null;
    }

    /**
     * Get the MIME type of the resource
     *
     * @return string|null The MIME type or null if unknown
     */
    public function getMimeType(): ?string
    {
        return null;
    }

    /**
     * Get the content of the resource
     *
     * @return string The content of the resource
     */
    abstract public function getContent(): string;

    public function getResponse(): ResourceResponse
    {
        return ResourceResponse::text($this);
    }
}
