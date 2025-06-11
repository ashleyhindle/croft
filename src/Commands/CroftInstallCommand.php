<?php

declare(strict_types=1);

namespace Croft\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CroftInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'croft:install';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Install Croft configuration for the project.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cursorDir = base_path('.cursor');
        $mcpJsonPath = $cursorDir.'/mcp.json';

        if (! File::exists($cursorDir)) {
            $this->comment('.cursor directory does not exist. Skipping mcp.json setup.');

            return static::SUCCESS;
        }

        if (! File::isDirectory($cursorDir)) {
            $this->error('.cursor exists but is not a directory. Please remove it and try again.');

            return static::FAILURE;
        }

        $this->info('Found .cursor directory.');

        $croftMcpConfig = [
            'command' => './artisan',
            'args' => ['croft'],
        ];

        if (! File::exists($mcpJsonPath)) {
            $this->info('mcp.json not found. Creating it...');
            $content = [
                'mcpServers' => [
                    'croft' => $croftMcpConfig,
                ],
            ];
            File::put($mcpJsonPath, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('mcp.json created successfully in .cursor directory.');
        } else {
            $this->info('Found mcp.json. Checking content...');
            $jsonContent = File::get($mcpJsonPath);
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Error decoding mcp.json: '.json_last_error_msg());
                $this->comment('Please check the mcp.json file format.');

                return static::FAILURE;
            }

            // A JSON object is expected. This decodes to an associative array or an empty array.
            // A JSON array (list) or scalar is not acceptable at the root.
            $isNonEmptyList = is_array($data) && ! empty($data) && array_keys($data) === range(0, count($data) - 1);

            if (! is_array($data) || $isNonEmptyList) {
                $this->error('mcp.json does not contain a valid JSON object.');
                $this->comment('Overwriting with default Croft configuration.');
                $data = [];
            }

            // Ensure mcpServers key exists and is an array
            if (! isset($data['mcpServers']) || ! is_array($data['mcpServers'])) {
                $data['mcpServers'] = [];
            }

            // Add or update the croft configuration
            $data['mcpServers']['croft'] = $croftMcpConfig;

            File::put($mcpJsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('mcp.json updated successfully with Croft server configuration.');
        }

        return static::SUCCESS;
    }
}
