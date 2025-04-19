<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class ListRoutes extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('List Routes')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'list_routes';
    }

    public function getDescription(): string
    {
        return 'List all available routes, returns regular routes & Folio routes (if installed)';
    }

    public function getInputSchema(): array
    {
        /* extra supported params for route:list:
            Options:
            --method[=METHOD]            Filter the routes by method
            --action[=ACTION]            Filter the routes by action
            --name[=NAME]                Filter the routes by name
            --domain[=DOMAIN]            Filter the routes by domain
            --path[=PATH]                Only show routes matching the given path pattern
            --except-path[=EXCEPT-PATH]  Do not display the routes matching the given path pattern
            --except-vendor              Do not display routes defined by vendor packages
            --only-vendor                Only display routes defined by vendor packages
            */
        return [
            'type' => 'object',
            'properties' => (object) [
                'params' => [
                    'type' => 'object',
                    'properties' => (object) [
                        'method' => [
                            'type' => 'string',
                            'description' => 'Filter the routes by method',
                        ],
                        'action' => [
                            'type' => 'string',
                            'description' => 'Filter the routes by action',
                        ],
                        'name' => [
                            'type' => 'string',
                            'description' => 'Filter the routes by name',
                        ],
                        'domain' => [
                            'type' => 'string',
                            'description' => 'Filter the routes by domain',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Only show routes matching the given path pattern',
                        ],
                        'except-path' => [
                            'type' => 'string',
                            'description' => 'Do not display the routes matching the given path pattern',
                        ],
                        'except-vendor' => [
                            'type' => 'boolean',
                            'description' => 'Do not display routes defined by vendor packages',
                        ],
                        'only-vendor' => [
                            'type' => 'boolean',
                            'description' => 'Only display routes defined by vendor packages',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            'required' => ['params'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $params = $arguments['params'];
        $options = [];
        foreach ($params as $key => $value) {
            if ($value !== null) {
                $options['--'.$key] = $value;
            }
        }

        $routeOutput = $this->artisan('route:list', $options);

        // If folio installed (or folio:list command exists), then call that and combine the results
        // If we call it without '--json' it just hangs, so we need to use the json output for now
        if (class_exists('Laravel\\Folio\\FolioRoutes')) {
            $routeOutput .= "\n\n===FOLIO ROUTES (JSON)===\n\n";
            // TODO: Reduce options to the ones folio:list support - pretty sure they don't match route:list
            $routeOutput .= $this->artisan('folio:list', array_merge($options, ['--no-ansi' => true, '--no-interaction' => true, '--json' => true]));
        }

        return ToolResponse::text($routeOutput);
    }

    private function artisan(string $command, array $options = []): string
    {
        $output = new BufferedOutput();
        Artisan::call($command, $options, $output);
        return trim($output->fetch());
    }
}
