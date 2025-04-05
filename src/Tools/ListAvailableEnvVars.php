<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Env;

class ListAvailableEnvVars extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('List Available Env Vars')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'list_available_env_vars';
    }

    public function getDescription(): string
    {
        return 'List all available environment variables (from .env)';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'filename' => [
                    'type' => 'string',
                    'description' => 'The filename of the .env file to read - .env or .env.example',
                ],
            ],
            'required' => ['filename'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        // Env::all() doesn't exist, and we don't want to include dotenv here, let's do a basic parse
        $filePath = base_path($arguments['filename'] ?? '.env');
        if (! file_exists($filePath)) {
            return ToolResponse::error('No .env file found');
        }

        $envLines = file($filePath);
        if ($envLines === false) {
            return ToolResponse::error('Failed to read .env file');
        }

        $envVars = [];
        foreach ($envLines as $line) {
            $line = trim($line);
            // Ignore commented out lines
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            // Split into key and value, and only add the key
            // We do not want to share our secrets with the MCP client
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $envVars[] = $parts[0];
            }
        }

        return ToolResponse::array($envVars);
    }
}
