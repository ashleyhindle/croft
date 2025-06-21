<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

class ArtisanMake extends AbstractTool
{
    public function __construct()
    {
        $this->setTitle('artisan_make')
            ->setReadOnly(false)
            ->setDestructive(false)
            ->setIdempotent(false);
    }

    public function getName(): string
    {
        return 'artisan_make';
    }

    public function getDescription(): string
    {
        return 'Generates Laravel classes using the artisan make command. Available class types: '.$this->getAvailableArtisanMakeTypes()->implode(', ');
    }

    /**
     * What params does the MCP client need to provide to use this tool?
     **/
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'type' => [
                    'type' => 'string',
                    'description' => 'The type of class to create (e.g., controller, model, job).',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the class to create.',
                ],
                'options' => [
                    'type' => 'object',
                    'description' => 'Additional options for the make command (e.g., {"--invokable": true}).',
                ],
            ],
            'required' => ['type', 'name'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $classType = $arguments['type'];
        $className = $arguments['name'];
        $options = (array) ($arguments['options'] ?? []);

        $availableArtisanMakeTypes = $this->getAvailableArtisanMakeTypes();

        if (! $availableArtisanMakeTypes->contains($classType)) {
            $availableTypesString = $availableArtisanMakeTypes->implode(', ');

            return ToolResponse::error("Invalid class type '{$classType}'. Available types for the artisan make command are: {$availableTypesString}.");
        }

        $command = "make:{$classType}";

        $params = array_merge(['name' => $className], $options);

        try {
            Artisan::call($command, $params);
            $output = Artisan::output();

            return ToolResponse::text($output);
        } catch (\Exception $e) {
            return ToolResponse::error('Error executing command: '.$e->getMessage());
        }
    }

    private function getAvailableArtisanMakeTypes(): Collection
    {
        return collect(Artisan::all())
            ->keys()
            ->filter(fn (string $command) => str_starts_with($command, 'make:'))
            ->map(fn (string $command) => str_replace('make:', '', $command))
            ->sort()
            ->values();
    }
}
