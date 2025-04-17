<?php

declare(strict_types=1);

namespace Croft\Tools\Flux;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;

class GetComponentExamples extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Get Flux Component Examples')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'get_flux_component_examples';
    }

    public function getDescription(): string
    {
        return 'Get usage examples for a specific Flux UI component';
    }

    /**
     * What params does the MCP client need to provide to use this tool?
     **/
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'componentName' => [
                    'type' => 'string',
                    'description' => 'Name of the Flux UI component (e.g., "accordion", "button", "date-picker")',
                ],
            ],
            'required' => ['componentName'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $componentDetails = $this->getComponentDetails($arguments['componentName']);
        if (is_null($componentDetails)) {
            return ToolResponse::error('Failed to fetch component details');
        }

        return ToolResponse::array($componentDetails['examples']);
    }

    private function getComponentDetails(string $componentName): ?array
    {
        $componentDetails = $this->cache->get('flux_component_details_'.$componentName);

        if (! $componentDetails) {
            $get = new GetComponentDetails;
            $get->setCache($this->cache);
            $response = $get->handle(['componentName' => $componentName]);
            if ($response->isError()) {
                return null;
            }

            // The 'get' class will have set the cache for us
            $componentDetails = $this->cache->get('flux_component_details_'.$componentName);
        }

        return $componentDetails;
    }
}
