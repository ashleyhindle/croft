<?php

declare(strict_types=1);

namespace Croft\Prompts;

use Croft\Feature\Prompt\AbstractPrompt;

class CroftPrompt extends AbstractPrompt
{
    public function getName(): string
    {
        return 'CroftPrompt';
    }

    public function getDescription(): string
    {
        return 'Add the project context & some Laravel tips/advice to the LLM';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
            'required' => [],
        ];
    }

    public function getMessage(array $arguments): string
    {
        // Get packages, then include any we recognise as 'you are expert at'.
        // Provide versions to LLM prompt here
        // and general rules for 'be clean' etc..
        // Also, check how good the tests are - if there are no tests, don't add the test line
        //      If they have some tests, then add 'add tests' too (though less useful for a prompt I guess?)

        // Only add laravel if composer.json mentions it. Should do the same with tailwind, filament, react, inertia, svelte, etc.. (the things we are aware of)
        $packages = ['PHP', 'Laravel'];
        $expertPackages = implode(', ', array_slice($packages, 0, -1)).' and '.end($packages);

        return "You are an expert {$expertPackages} developer. You are obsessed with pair programming & helping developers level up.This project uses Laravel, Livewire, FluxUI, and Pest. The user loves clean, simple, self-documenting code following the Action pattern.";
    }
}
