<?php

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

beforeEach(function () {
    // Clean up created directories and files after each test
    File::delete(base_path('.cursor/mcp.json'));

    if (file_exists(base_path('.cursor'))) {
        rmdir(base_path('.cursor'));
    }
});

it('creates cursor directory and mcp json if directory does not exist', function () {
    // Given: .cursor directory does not exist
    expect(File::exists(base_path('.cursor')))->toBeFalse();

    // Pre-condition
    // When
    $this->artisan('croft:install')
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(0);

    // Then
    expect(File::exists(base_path('.cursor/mcp.json')))->toBeTrue();
    $expectedContent = [
        'mcpServers' => [
            'croft-local' => [
                'command' => './artisan',
                'args' => ['croft'],
            ],
        ],
    ];
    expect(json_decode(File::get(base_path('.cursor/mcp.json')), true))->toEqual($expectedContent);
});

it('errors if cursor is a file not a directory when cursor is chosen', function () {
    File::put(base_path('.cursor'), 'not a directory');

    $this->artisan('croft:install')
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(1);

    // Ensure the file is cleaned up for subsequent tests if an assertion fails before tearDown runs
    if (File::exists(base_path('.cursor'))) {
        File::delete(base_path('.cursor'));
    }
});

it('creates mcp json if cursor dir exists but mcp json does not when cursor is chosen', function () {
    File::makeDirectory(base_path('.cursor'));

    $this->artisan('croft:install')
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(0);

    expect(File::exists(base_path('.cursor/mcp.json')))->toBeTrue();
    $expectedContent = [
        'mcpServers' => [
            'croft-local' => [
                'command' => './artisan',
                'args' => ['croft'],
            ],
        ],
    ];
    expect(json_decode(File::get(base_path('.cursor/mcp.json')), true))->toEqual($expectedContent);
});

it('updates mcp json if it exists and is empty object when cursor is chosen', function () {
    File::makeDirectory(base_path('.cursor'));
    File::put(base_path('.cursor/mcp.json'), '{}');

    $this->artisan('croft:install')
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(0);

    $expectedContent = [
        'mcpServers' => [
            'croft-local' => [
                'command' => './artisan',
                'args' => ['croft'],
            ],
        ],
    ];
    expect(json_decode(File::get(base_path('.cursor/mcp.json')), true))->toEqual($expectedContent);
});

it('updates mcp json if it exists and has other mcp servers when cursor is chosen', function () {
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
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(0);

    $expectedContent = [
        'mcpServers' => [
            'anotherServer' => [
                'command' => 'do_something_else',
                'args' => [],
            ],
            'croft-local' => [
                'command' => './artisan',
                'args' => ['croft'],
            ],
        ],
    ];
    expect(json_decode(File::get(base_path('.cursor/mcp.json')), true))->toEqual($expectedContent);
});

it('overwrites existing croft config in mcp json when cursor is chosen', function () {
    File::makeDirectory(base_path('.cursor'));
    $initialContent = [
        'mcpServers' => [
            'croft-local' => [
                'command' => 'old_command',
                'args' => ['old_arg'],
            ],
        ],
    ];
    File::put(base_path('.cursor/mcp.json'), json_encode($initialContent, JSON_PRETTY_PRINT));

    $this->artisan('croft:install')
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(0);

    $expectedContent = [
        'mcpServers' => [
            'croft-local' => [
                'command' => './artisan',
                'args' => ['croft'],
            ],
        ],
    ];
    expect(json_decode(File::get(base_path('.cursor/mcp.json')), true))->toEqual($expectedContent);
});

it('handles invalid json in mcp json file when cursor is chosen', function () {
    File::makeDirectory(base_path('.cursor'));
    File::put(base_path('.cursor/mcp.json'), 'invalid json content');

    $this->artisan('croft:install')
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(1);
});

it('handles mcp json that is not a json object at root when cursor is chosen', function () {
    File::makeDirectory(base_path('.cursor'));
    File::put(base_path('.cursor/mcp.json'), json_encode(['not_an_object_at_root']));

    $this->artisan('croft:install')
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(0);

    $expectedContent = [
        'mcpServers' => [
            'croft-local' => [
                'command' => './artisan',
                'args' => ['croft'],
            ],
        ],
    ];
    expect(json_decode(File::get(base_path('.cursor/mcp.json')), true))->toEqual($expectedContent);
});

it('creates mcp servers key if not present in mcp json when cursor is chosen', function () {
    File::makeDirectory(base_path('.cursor'));
    File::put(base_path('.cursor/mcp.json'), json_encode(['some_other_key' => 'some_value']));

    $this->artisan('croft:install')
        ->expectsChoice('Which editor are you using?', 'cursor', getEditorChoices())
        ->assertExitCode(0);

    $expectedContent = [
        'some_other_key' => 'some_value',
        'mcpServers' => [
            'croft-local' => [
                'command' => './artisan',
                'args' => ['croft'],
            ],
        ],
    ];
    expect(json_decode(File::get(base_path('.cursor/mcp.json')), true))->toEqual($expectedContent);
});
