<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateCroftResource extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Create Croft MCP Server Resource')
            ->setReadOnly(false)        // This tool modifies the filesystem
            ->setDestructive(false)     // Not destructive as it creates new files
            ->setIdempotent(false)      // Creating the same tool twice would error
            ->setOpenWorld(false);      // Limited to creating tools
    }

    public function getName(): string
    {
        return 'create_croft_mcp_server_resource';
    }

    public function getDescription(): string
    {
        return 'Create a new Croft MCP Server resource in the App/Croft/Resources namespace. This should only be used when the user wants to add a new resource to their MCP server.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the resource to create (without "Resource" suffix). i.e. "LatestExpense"',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of what the resource does',
                ],
            ],
            'required' => ['name', 'description'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $name = $arguments['name'];
        $description = $arguments['description'];

        // Create class name (append "Resource" if not already present)
        $className = Str::studly($name);
        if (! Str::endsWith($className, 'Resource')) {
            $className .= 'Resource';
        }

        // Create resource name in snake_case
        $resourceName = Str::snake($name);

        // Ensure App/Resources directory exists
        $resourcesDirectory = base_path('app/Croft/Resources');
        if (! File::isDirectory($resourcesDirectory)) {
            File::makeDirectory($resourcesDirectory, 0755, true);
        }

        // Get stub content
        $stubPath = __DIR__.'/../../stubs/resource.php.stub';
        $stubContent = File::get($stubPath);

        // Replace placeholders
        $content = str_replace('{{CLASSNAME}}', $className, $stubContent);
        $content = str_replace('{{NAME}}', $resourceName, $content);
        $content = str_replace('{{DESCRIPTION}}', $description, $content);

        // Save the new resource file
        $resourcePath = $resourcesDirectory.'/'.$className.'.php';
        File::put($resourcePath, $content);

        return ToolResponse::text("Resource '{$className}' created successfully at {$resourcePath}. Once updated it will need to be enabled in the config/croft.php file.");
    }
}
