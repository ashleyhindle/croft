<?php

namespace Croft\Tests\Commands;

use Croft\Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;

class CroftInstallWindsurfCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        // Ensure no .cursor directory or mcp.json is left if a test fails unexpectedly
        File::delete(base_path('.cursor/mcp.json'));
        File::deleteDirectory(base_path('.cursor'));
        parent::tearDown();
    }

    private function getEditorChoices(): array
    {
        return ['cursor' => 'Cursor', 'windsurf' => 'Windsurf', 'phpstorm' => 'PhpStorm (coming soon)'];
    }

    #[Test]
    public function it_shows_windsurf_instructions_when_windsurf_is_chosen()
    {
        $this->artisan('croft:install')
            ->expectsChoice('Which editor are you using?', 'windsurf', $this->getEditorChoices())
            ->expectsOutput('To configure Windsurf, please see: <href=https://docs.windsurf.com/windsurf/cascade/mcp#mcp-config-json>https://docs.windsurf.com/windsurf/cascade/mcp#mcp-config-json</>')
            ->expectsOutput('Windsurf setup is manual for now. Please follow the link above.')
            ->assertExitCode(0);

        // Ensure no .cursor directory or mcp.json was created
        $this->assertFalse(File::exists(base_path('.cursor')));
        $this->assertFalse(File::exists(base_path('.cursor/mcp.json')));
    }
}
