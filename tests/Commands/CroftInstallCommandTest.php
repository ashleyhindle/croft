<?php

namespace Croft\Tests\Commands;

use Croft\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class CroftInstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up created directories and files after each test
        File::delete(base_path('.cursor/mcp.json'));
        File::deleteDirectory(base_path('.cursor'));

        parent::tearDown();
    }

    #[Test]
    public function it_does_nothing_if_cursor_directory_does_not_exist()
    {
        $this->artisan('croft:install')
            ->expectsOutput('.cursor directory does not exist. Skipping mcp.json setup.')
            ->assertExitCode(0);

        $this->assertFalse(File::exists(base_path('.cursor/mcp.json')));
    }

    #[Test]
    public function it_errors_if_cursor_is_a_file_not_a_directory()
    {
        File::put(base_path('.cursor'), 'not a directory');

        $this->artisan('croft:install')
            ->expectsOutput('.cursor exists but is not a directory. Please remove it and try again.')
            ->assertExitCode(1);

        // Ensure the file is cleaned up for subsequent tests if an assertion fails before tearDown runs
        if (File::exists(base_path('.cursor'))) {
            File::delete(base_path('.cursor'));
        }
    }

    #[Test]
    public function it_creates_mcp_json_if_cursor_dir_exists_but_mcp_json_does_not()
    {
        File::makeDirectory(base_path('.cursor'));

        $this->artisan('croft:install')
            ->expectsOutput('Found .cursor directory.')
            ->expectsOutput('mcp.json not found. Creating it...')
            ->expectsOutput('mcp.json created successfully in .cursor directory.')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(base_path('.cursor/mcp.json')));
        $expectedContent = [
            'mcpServers' => [
                'croft' => [
                    'command' => './artisan',
                    'args' => ['croft'],
                ],
            ],
        ];
        $this->assertEquals($expectedContent, json_decode(File::get(base_path('.cursor/mcp.json')), true));
    }

    #[Test]
    public function it_updates_mcp_json_if_it_exists_and_is_empty_object()
    {
        File::makeDirectory(base_path('.cursor'));
        File::put(base_path('.cursor/mcp.json'), '{}');

        $this->artisan('croft:install')
            ->expectsOutput('Found .cursor directory.')
            ->expectsOutput('Found mcp.json. Checking content...')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration.')
            ->assertExitCode(0);

        $expectedContent = [
            'mcpServers' => [
                'croft' => [
                    'command' => './artisan',
                    'args' => ['croft'],
                ],
            ],
        ];
        $this->assertEquals($expectedContent, json_decode(File::get(base_path('.cursor/mcp.json')), true));
    }

    #[Test]
    public function it_updates_mcp_json_if_it_exists_and_has_other_mcp_servers()
    {
        File::makeDirectory(base_path('.cursor'));
        $initialContent = [
            'mcpServers' => [
                'anotherServer' => [
                    'command' => 'do_something_else',
                    'args' => [],
                ],
            ],
        ];
        File::put(base_path('.cursor/mcp.json'), json_encode($initialContent, JSON_PRETTY_PRINT));

        $this->artisan('croft:install')
            ->expectsOutput('Found .cursor directory.')
            ->expectsOutput('Found mcp.json. Checking content...')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration.')
            ->assertExitCode(0);

        $expectedContent = [
            'mcpServers' => [
                'anotherServer' => [
                    'command' => 'do_something_else',
                    'args' => [],
                ],
                'croft' => [
                    'command' => './artisan',
                    'args' => ['croft'],
                ],
            ],
        ];
        $this->assertEquals($expectedContent, json_decode(File::get(base_path('.cursor/mcp.json')), true));
    }

    #[Test]
    public function it_overwrites_existing_croft_config_in_mcp_json()
    {
        File::makeDirectory(base_path('.cursor'));
        $initialContent = [
            'mcpServers' => [
                'croft' => [
                    'command' => 'old_command',
                    'args' => ['old_arg'],
                ],
            ],
        ];
        File::put(base_path('.cursor/mcp.json'), json_encode($initialContent, JSON_PRETTY_PRINT));

        $this->artisan('croft:install')
            ->expectsOutput('Found .cursor directory.')
            ->expectsOutput('Found mcp.json. Checking content...')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration.')
            ->assertExitCode(0);

        $expectedContent = [
            'mcpServers' => [
                'croft' => [
                    'command' => './artisan',
                    'args' => ['croft'],
                ],
            ],
        ];
        $this->assertEquals($expectedContent, json_decode(File::get(base_path('.cursor/mcp.json')), true));
    }

    #[Test]
    public function it_handles_invalid_json_in_mcp_json_file()
    {
        File::makeDirectory(base_path('.cursor'));
        File::put(base_path('.cursor/mcp.json'), 'invalid json content');

        $this->artisan('croft:install')
            ->expectsOutput('Found .cursor directory.')
            ->expectsOutput('Found mcp.json. Checking content...')
            ->expectsOutput('Error decoding mcp.json: Syntax error')
            ->expectsOutput('Please check the mcp.json file format.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_handles_mcp_json_that_is_not_a_json_object_at_root()
    {
        File::makeDirectory(base_path('.cursor'));
        File::put(base_path('.cursor/mcp.json'), json_encode(['not_an_object_at_root']));

        $this->artisan('croft:install')
            ->expectsOutput('Found .cursor directory.')
            ->expectsOutput('Found mcp.json. Checking content...')
            ->expectsOutput('mcp.json does not contain a valid JSON object.')
            ->expectsOutput('Overwriting with default Croft configuration.')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration.')
            ->assertExitCode(0);

        $expectedContent = [
            'mcpServers' => [
                'croft' => [
                    'command' => './artisan',
                    'args' => ['croft'],
                ],
            ],
        ];
        $this->assertEquals($expectedContent, json_decode(File::get(base_path('.cursor/mcp.json')), true));
    }

    #[Test]
    public function it_creates_mcp_servers_key_if_not_present_in_mcp_json()
    {
        File::makeDirectory(base_path('.cursor'));
        File::put(base_path('.cursor/mcp.json'), json_encode(['some_other_key' => 'some_value']));

        $this->artisan('croft:install')
            ->expectsOutput('Found .cursor directory.')
            ->expectsOutput('Found mcp.json. Checking content...')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration.')
            ->assertExitCode(0);

        $expectedContent = [
            'some_other_key' => 'some_value',
            'mcpServers' => [
                'croft' => [
                    'command' => './artisan',
                    'args' => ['croft'],
                ],
            ],
        ];
        $this->assertEquals($expectedContent, json_decode(File::get(base_path('.cursor/mcp.json')), true));
    }
}
