<?php

use Croft\Tests\TestCase;
use Symfony\Component\Console\Question\ChoiceQuestion;

uses(TestCase::class)->in(__DIR__);

function getEditorChoices(): array
{
    $choices = new ChoiceQuestion('Which editor are you using?', ['cursor' => 'Cursor', 'windsurf' => 'Windsurf', 'phpstorm' => 'PhpStorm (coming soon)']);

    return $choices->getAutocompleterValues();
}
