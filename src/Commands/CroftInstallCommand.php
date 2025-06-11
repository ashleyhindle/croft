<?php

declare(strict_types=1);

namespace Croft\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CroftInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    public $signature = 'croft:install';

    /**
     * The console command description.
     */
    public $description = 'Install Croft configuration for the project.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $editor = $this->choice(
            'Which editor are you using?',
            ['cursor' => 'Cursor', 'windsurf' => 'Windsurf', 'phpstorm' => 'PhpStorm (coming soon)'],
            'cursor'
        );

        switch ($editor) {
            case 'cursor':
                return $this->installForCursor();
            case 'windsurf':
                $this->line('To configure Windsurf, please see: <href=https://docs.windsurf.com/windsurf/cascade/mcp#mcp-config-json>https://docs.windsurf.com/windsurf/cascade/mcp#mcp-config-json</>');
                $this->comment('Windsurf setup is manual for now. Please follow the link above.');
                break;
            case 'phpstorm':
                $this->comment('PhpStorm integration is coming soon. Please check back later.');
                break;
            default:
                $this->error('Invalid editor selection.');

                return static::FAILURE;
        }

        return static::SUCCESS;
    }

    /**
     * Handles the installation process for Cursor editor.
     */
    protected function installForCursor(): int
    {
        $cursorDir = base_path('.cursor');
        $mcpJsonPath = $cursorDir . '/mcp.json';

        if (!File::exists($cursorDir)) {
            $this->info('.cursor directory not found. Creating it...');
            File::makeDirectory($cursorDir);
            $this->info('.cursor directory created successfully.');
            // Now proceed to create mcp.json as if the directory was just found empty
        }

        if (!File::isDirectory($cursorDir)) {
            $this->error('.cursor exists but is not a directory. Please remove it and try again.');

            return static::FAILURE;
        }

        $this->info('Found .cursor directory for Cursor.');

        $croftMcpConfig = [
            'command' => './artisan',
            'args' => ['croft'],
        ];

        // If mcp.json does not exist (which will be true if .cursor was just created)
        // or if it existed but was empty/invalid, this block will handle it.
        if (!File::exists($mcpJsonPath)) {
            $this->info('mcp.json not found for Cursor. Creating it...');
            $content = [
                'mcpServers' => [
                    'croft' => $croftMcpConfig,
                ],
            ];
            File::put($mcpJsonPath, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('mcp.json created successfully in .cursor directory for Cursor.');
        } else {
            $this->info('Found mcp.json for Cursor. Checking content...');
            $jsonContent = File::get($mcpJsonPath);
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Error decoding mcp.json: '.json_last_error_msg());
                $this->comment('Please check the mcp.json file format for Cursor.');

                return static::FAILURE;
            }

            // A JSON object is expected. This decodes to an associative array or an empty array.
            // A JSON array (list) or scalar is not acceptable at the root.
            $isNonEmptyList = is_array($data) && ! empty($data) && array_keys($data) === range(0, count($data) - 1);

            if (! is_array($data) || $isNonEmptyList) {
                $this->error('mcp.json for Cursor does not contain a valid JSON object.');
                $this->comment('Overwriting with default Croft configuration for Cursor.');
                $data = [];
            }

            // Ensure mcpServers key exists and is an array
            if (! isset($data['mcpServers']) || ! is_array($data['mcpServers'])) {
                $data['mcpServers'] = [];
            }

            // Add or update the croft configuration
            $data['mcpServers']['croft'] = $croftMcpConfig;

            File::put($mcpJsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('mcp.json updated successfully with Croft server configuration for Cursor.');
        }

        return static::SUCCESS;
    }
}
