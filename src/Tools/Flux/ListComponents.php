<?php

declare(strict_types=1);

namespace Croft\Tools\Flux;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Croft\Http\HttpClient;

class ListComponents extends AbstractTool
{
    private const FLUX_DOCS_URL = 'https://fluxui.dev';

    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('List Flux Components')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(true);     // Accessing external URLs
    }

    public function getName(): string
    {
        return 'list_flux_components';
    }

    public function getDescription(): string
    {
        return 'Get, or search, a list of all available Flux UI components from the documentation site';
    }

    /**
     * What params does the MCP client need to provide to use this tool?
     **/
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'search' => [
                    'type' => 'string',
                    'description' => 'Search for a specific component by name (optional)',
                ],
            ],
            'required' => [],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        try {
            // Check cache first
            if ($this->cache->has('flux_components_list')) {
                $components = $this->cache->get('flux_components_list');
            } else {
                // Fetch components list
                $response = HttpClient::getInstance()->get(self::FLUX_DOCS_URL.'/components');

                if (! $response->successful()) {
                    return ToolResponse::error('Failed to fetch components list: HTTP '.$response->status());
                }

                $components = $this->extractComponents($response->body());
                $this->cache->set('flux_components_list', $components);
            }

            if (isset($arguments['search'])) {
                $components = array_filter($components, function ($component) use ($arguments) {
                    // TODO: Enhance with component details, then search across description and examples
                    return stripos($component['name'], $arguments['search']) !== false;
                });
            }

            return ToolResponse::array(array_values($components));
        } catch (\Exception $e) {
            return ToolResponse::error('Failed to fetch components list: '.$e->getMessage());
        }
    }

    private function extractComponents(string $html): array
    {
        $components = [];

        // Find all component links in the documentation
        if (preg_match_all('/<a[^>]*href="\/components\/(.*?)"[^>]*>(.*?)</s', $html, $matches)) {
            foreach ($matches[1] as $index => $slug) {
                $name = strip_tags($matches[2][$index]);
                $components[] = [
                    'name' => trim($name),
                    'slug' => $slug,
                    'url' => self::FLUX_DOCS_URL.'/components/'.$slug,
                ];
            }
        }

        return $components;
    }
}
