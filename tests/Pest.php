<?php

use Croft\Tests\TestCase;

use function Orchestra\Testbench\laravel_version_compare;

uses(TestCase::class)->in(__DIR__);

function getEditorChoices(): array
{
    $choices = ['cursor' => 'Cursor', 'windsurf' => 'Windsurf', 'phpstorm' => 'PhpStorm (coming soon)'];
    if (laravel_version_compare('11.20', '<=')) {
        $choices = array_merge($choices, ['cursor', 'phpstorm', 'windsurf']);
    }

    return $choices;
}
