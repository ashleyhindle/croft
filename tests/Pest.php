<?php

use Croft\Tests\TestCase;

use function Orchestra\Testbench\laravel_version_compare;

uses(TestCase::class)->in(__DIR__);

function getEditorChoices(): array
{
    $choices = ['cursor' => 'Cursor', 'windsurf' => 'Windsurf', 'phpstorm' => 'PhpStorm (coming soon)'];
    if (laravel_version_compare('12', '<')) {
        $choices = ['Cursor', 'PhpStorm (coming soon)', 'Windsurf', 'cursor', 'phpstorm', 'windsurf'];
    }

    return $choices;
}
