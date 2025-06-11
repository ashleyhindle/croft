<?php

namespace Croft\Tests\Commands;

use Croft\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class CroftInstallCursorCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up created directories and files after each test
        File::delete(base_path('.cursor/mcp.json'));
        File::deleteDirectory(base_path('.cursor'));

        parent::tearDown();
    }

    private function getEditorChoices(): array
    {
        return ['cursor' => 'Cursor', 'windsurf' => 'Windsurf', 'phpstorm' => 'PhpStorm (coming soon)'];
    }

    #[Test]
    public function it_creates_cursor_directory_and_mcp_json_if_directory_does_not_exist()
    {
        // Given: .cursor directory does not exist
        $this->assertFalse(File::exists(base_path('.cursor'))); // Pre-condition

        // When
        $this->artisan('croft:install')
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('.cursor directory not found. Creating it...')
            ->expectsOutput('.cursor directory created successfully.')
            ->expectsOutput('Found .cursor directory for Cursor.')
            ->expectsOutput('mcp.json not found for Cursor. Creating it...')
            ->expectsOutput('mcp.json created successfully in .cursor directory for Cursor.')
            ->assertExitCode(0);

        // Then
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
    public function it_errors_if_cursor_is_a_file_not_a_directory_when_cursor_is_chosen()
    {
        File::put(base_path('.cursor'), 'not a directory');

        $this->artisan('croft:install')
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('.cursor exists but is not a directory. Please remove it and try again.')
            ->assertExitCode(1);

        // Ensure the file is cleaned up for subsequent tests if an assertion fails before tearDown runs
        if (File::exists(base_path('.cursor'))) {
            File::delete(base_path('.cursor'));
        }
    }

    #[Test]
    public function it_creates_mcp_json_if_cursor_dir_exists_but_mcp_json_does_not_when_cursor_is_chosen()
    {
        File::makeDirectory(base_path('.cursor'));

        $this->artisan('croft:install')
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('Found .cursor directory for Cursor.')
            ->expectsOutput('mcp.json not found for Cursor. Creating it...')
            ->expectsOutput('mcp.json created successfully in .cursor directory for Cursor.')
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
    public function it_updates_mcp_json_if_it_exists_and_is_empty_object_when_cursor_is_chosen()
    {
        File::makeDirectory(base_path('.cursor'));
        File::put(base_path('.cursor/mcp.json'), '{}');

        $this->artisan('croft:install')
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('Found .cursor directory for Cursor.')
            ->expectsOutput('Found mcp.json for Cursor. Checking content...')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration for Cursor.')
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
    public function it_updates_mcp_json_if_it_exists_and_has_other_mcp_servers_when_cursor_is_chosen()
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
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('Found .cursor directory for Cursor.')
            ->expectsOutput('Found mcp.json for Cursor. Checking content...')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration for Cursor.')
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
    public function it_overwrites_existing_croft_config_in_mcp_json_when_cursor_is_chosen()
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
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('Found .cursor directory for Cursor.')
            ->expectsOutput('Found mcp.json for Cursor. Checking content...')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration for Cursor.')
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
    public function it_handles_invalid_json_in_mcp_json_file_when_cursor_is_chosen()
    {
        File::makeDirectory(base_path('.cursor'));
        File::put(base_path('.cursor/mcp.json'), 'invalid json content');

        $this->artisan('croft:install')
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('Found .cursor directory for Cursor.')
            ->expectsOutput('Found mcp.json for Cursor. Checking content...')
            ->expectsOutput('Error decoding mcp.json: Syntax error')
            ->expectsOutput('Please check the mcp.json file format for Cursor.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_handles_mcp_json_that_is_not_a_json_object_at_root_when_cursor_is_chosen()
    {
        File::makeDirectory(base_path('.cursor'));
        File::put(base_path('.cursor/mcp.json'), json_encode(['not_an_object_at_root']));

        $this->artisan('croft:install')
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('Found .cursor directory for Cursor.')
            ->expectsOutput('Found mcp.json for Cursor. Checking content...')
            ->expectsOutput('mcp.json for Cursor does not contain a valid JSON object.')
            ->expectsOutput('Overwriting with default Croft configuration for Cursor.')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration for Cursor.')
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
    public function it_creates_mcp_servers_key_if_not_present_in_mcp_json_when_cursor_is_chosen()
    {
        File::makeDirectory(base_path('.cursor'));
        File::put(base_path('.cursor/mcp.json'), json_encode(['some_other_key' => 'some_value']));

        $this->artisan('croft:install')
            ->expectsChoice('Which editor are you using?', 'cursor', $this->getEditorChoices())
            ->expectsOutput('Found .cursor directory for Cursor.')
            ->expectsOutput('Found mcp.json for Cursor. Checking content...')
            ->expectsOutput('mcp.json updated successfully with Croft server configuration for Cursor.')
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
