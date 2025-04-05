<?php

declare(strict_types=1);

namespace Croft\Feature;

/**
 * Abstract base class for MCP feature registries
 *
 * Provides common functionality for all registry types (Tool, Prompt, Resource)
 */
abstract class AbstractRegistry
{
    /**
     * @var array<string, object> Map of item name to item instance
     */
    protected array $items = [];

    /**
     * Register an item with the registry
     *
     * @param  object  $item  The item to register
     * @return self Returns $this for method chaining
     *
     * @throws \Croft\Exceptions\ProtocolException If an item with the same name is already registered
     */
    public function register(object $item): self
    {
        if (!$this->validateItem($item)) {
            throw new \InvalidArgumentException('Invalid item provided to register: ' . get_class($item));
        }

        if (array_key_exists($item->getName(), $this->items)) {
            throw new \InvalidArgumentException('Item with name ' . $item->getName() . ' already registered');
        }

        $this->items[$item->getName()] = $item;

        return $this;
    }

    abstract protected function validateItem(object $item): bool;

    /**
     * Get an item by name
     *
     * @param  string  $name  The item name
     * @return object|null The item instance, or null if not found
     */
    public function getItem(string $name): ?object
    {
        return $this->items[$name] ?? null;
    }

    /**
     * Check if an item exists
     *
     * @param  string  $name  The name of the item to check
     * @return bool True if the item exists, false otherwise
     */
    public function hasItem(string $name): bool
    {
        return isset($this->items[$name]);
    }

    /**
     * Get all registered items
     *
     * @return array<string, object> Map of item name to item instance
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get schemas for all registered items in the format required by the MCP protocol
     *
     * @return array Item schemas
     */
    abstract public function getSchemas(): array;

    /**
     * Get the number of registered items
     *
     * @return int The number of items
     */
    public function count(): int
    {
        return count($this->items);
    }
}
