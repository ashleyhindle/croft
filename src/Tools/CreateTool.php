<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateTool extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Create Tool')
            ->setReadOnly(false)        // This tool modifies the filesystem
            ->setDestructive(false)     // Not destructive as it creates new files
            ->setIdempotent(false)      // Creating the same tool twice would error
            ->setOpenWorld(false);      // Limited to creating tools
    }

    public function getName(): string
    {
        return 'create_tool';
    }

    public function getDescription(): string
    {
        return 'Create a new tool in the App/Tools namespace';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the tool to create (without "Tool" suffix)',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of what the tool does',
                ],
                'read_only' => [
                    'type' => 'boolean',
                    'description' => 'Whether the tool is read-only',
                    'default' => true,
                ],
                'destructive' => [
                    'type' => 'boolean',
                    'description' => 'Whether the tool performs destructive operations',
                    'default' => false,
                ],
                'idempotent' => [
                    'type' => 'boolean',
                    'description' => 'Whether the tool is safe to retry',
                    'default' => true,
                ],
            ],
            'required' => ['name', 'description'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $name = $arguments['name'];
        $description = $arguments['description'];
        $readOnly = $arguments['read_only'] ?? true;
        $destructive = $arguments['destructive'] ?? false;
        $idempotent = $arguments['idempotent'] ?? true;

        // Create class name (append "Tool" if not already present)
        $className = Str::studly($name);
        if (! Str::endsWith($className, 'Tool')) {
            $className .= 'Tool';
        }

        // Create tool name in snake_case
        $toolName = Str::snake($name);

        // Ensure App/Tools directory exists
        $toolsDirectory = base_path('app/Tools');
        if (! File::isDirectory($toolsDirectory)) {
            File::makeDirectory($toolsDirectory, 0755, true);
        }

        // Get stub content
        $stubPath = __DIR__.'/../../stubs/tool.php.stub';
        $stubContent = File::get($stubPath);

        // Replace placeholders
        $content = str_replace('{{CLASSNAME}}', $className, $stubContent);
        $content = str_replace('{{NAME}}', $toolName, $content);

        // Replace namespace
        $content = str_replace('namespace Croft\Tools;', 'namespace App\Tools;', $content);

        // Update tool properties based on arguments
        $readOnlyStr = $readOnly ? 'true' : 'false';
        $destructiveStr = $destructive ? 'true' : 'false';
        $idempotentStr = $idempotent ? 'true' : 'false';

        // Update constructor
        $constructorPattern = "/setTitle\('{{NAME}}'\)\s+->setReadOnly\(true\)\s+->setDestructive\(false\)\s+->setIdempotent\(true\);/s";
        $constructorReplacement = "setTitle('$toolName')
            ->setReadOnly($readOnlyStr)        // ".($readOnly ? 'Just reading data, no modifications' : 'This tool modifies data')."
            ->setDestructive($destructiveStr)    // ".($destructive ? 'Performs destructive operations' : 'No destructive operations')."
            ->setIdempotent($idempotentStr);     // ".($idempotent ? 'Safe to retry' : 'Not safe to retry').'';
        $content = preg_replace($constructorPattern, $constructorReplacement, $content);

        // Update description
        $descriptionPattern = "/return 'Must explain well what the tool can do so the MCP client can decide when to use it.';/";
        $descriptionReplacement = "return '$description';";
        $content = preg_replace($descriptionPattern, $descriptionReplacement, $content);

        // Save the new tool file
        $toolPath = $toolsDirectory.'/'.$className.'.php';
        File::put($toolPath, $content);

        return ToolResponse::text("Tool '{$className}' created successfully at {$toolPath}");
    }
}
