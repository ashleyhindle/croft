<?php

use Croft\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function getEditorChoices(): array
{
    return ['cursor' => 'Cursor', 'windsurf' => 'Windsurf', 'phpstorm' => 'PhpStorm (coming soon)', 'cursor', 'phpstorm', 'windsurf'];
}
