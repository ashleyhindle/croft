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
        $editorResult = self::FAILURE;

        switch ($editor) {
            case 'cursor':
                $editorResult = $this->installForCursor();
                break;
            case 'windsurf':
                $editorResult = $this->installForWindsurf();
                break;
            case 'phpstorm':
                $this->comment('PhpStorm integration is coming soon. Please check back later.');
                break;
            default:
                $this->error('Invalid editor selection.');

                return static::FAILURE;
        }

        if ($editorResult === self::FAILURE) {
            return $editorResult;
        }

        $this->info('Publishing config file..');
        $this->call('vendor:publish', [
            '--tag' => 'croft-config',
        ]);

        return static::SUCCESS;
    }

    protected function getProjectName(): string
    {
        $parts = explode(DIRECTORY_SEPARATOR, base_path());

        return end($parts);
    }

    /**
     * Handles the installation process for Cursor editor.
     */
    protected function installForCursor(): int
    {
        return $this->installToMcpJson(base_path('.cursor'), 'Cursor');
    }

    protected function installForWindsurf(): int
    {
        return $this->installToMcpJson(getenv('HOME').'/.codeium/windsurf', 'Windsurf', 'mcp_config.json', true);
    }

    protected function installToMcpJson(string $mcpDir, string $editor, string $filename = 'mcp.json', bool $absolute = false): int
    {
        $projectName = $this->getProjectName();
        $mcpJsonPath = $mcpDir.'/'.$filename;

        if (! File::exists($mcpDir)) {
            $this->info($mcpDir.' directory not found. Creating it...');
            File::makeDirectory($mcpDir, 0755, true);
            $this->info($mcpDir.' directory created successfully.');
        }

        if (! File::isDirectory($mcpDir)) {
            $this->error($mcpDir.' exists but is not a directory. Please remove it and try again.');

            return static::FAILURE;
        }

        $this->info('Found '.$mcpDir.' directory for '.$editor);

        if ($absolute) {
            $croftMcpConfig = [
                'command' => PHP_BINARY,
                'args' => [base_path('artisan'), 'croft'],
            ];
        } else {
            $croftMcpConfig = [
                'command' => './artisan',
                'args' => ['croft'],
            ];
        }

        $serverKey = $absolute ? 'croft-'.$projectName : 'croft-local';

        // If mcp.json does not exist (which will be true if .cursor was just created)
        // or if it existed but was empty/invalid, this block will handle it.
        if (! File::exists($mcpJsonPath)) {
            $this->info($filename.' not found for '.$mcpDir.'. Creating it...');
            $content = [
                'mcpServers' => [
                    $serverKey => $croftMcpConfig,
                ],
            ];

            File::put($mcpJsonPath, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info($filename.' created successfully in '.$mcpDir.' directory for '.$editor);
        } else {
            $this->info('Found '.$filename.' for '.$editor.'. Checking content...');
            $jsonContent = File::get($mcpJsonPath);
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Error decoding '.$filename.': '.json_last_error_msg());
                $this->comment('Please check the '.$filename.' file format for '.$editor.'.');

                return static::FAILURE;
            }

            // A JSON object is expected. This decodes to an associative array or an empty array.
            // A JSON array (list) or scalar is not acceptable at the root.
            $isNonEmptyList = is_array($data) && ! empty($data) && array_keys($data) === range(0, count($data) - 1);

            if (! is_array($data) || $isNonEmptyList) {
                $this->error($filename.' for '.$mcpDir.' does not contain a valid JSON object.');
                $this->comment('Overwriting with default Croft configuration for '.$editor.'.');
                $data = [];
            }

            // Ensure mcpServers key exists and is an array
            if (! isset($data['mcpServers']) || ! is_array($data['mcpServers'])) {
                $data['mcpServers'] = [];
            }

            // Add or update the croft configuration
            $data['mcpServers'][$serverKey] = $croftMcpConfig;

            File::put($mcpJsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info($mcpDir.' updated successfully with Croft server configuration for '.$editor);
        }

        return static::SUCCESS;
    }
}
