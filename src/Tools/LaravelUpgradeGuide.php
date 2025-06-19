<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;

class LaravelUpgradeGuide extends AbstractTool
{
    private string $laravelVersion;

    public function __construct()
    {
        $this->laravelVersion = app()->version();
    }

    public function getName(): string
    {
        return 'laravel_upgrade_guide';
    }

    public function getDescription(): string
    {
        return 'Get the steps to upgrade to the next major version of Laravel';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
            'required' => [],
        ];
    }

    // We only have a guide for Laravel 10 -> 11 as a test, so this is a bit messy/unhelpful
    public function shouldRegister(): bool
    {
        return version_compare($this->laravelVersion, '10.0', '>=') && version_compare($this->laravelVersion, '11.0', '<');
    }

    // We only have a guide for Laravel 10 -> 11 as a test, so this is a bit messy/unhelpful
    public function handle(array $arguments): ToolResponse
    {
        if (version_compare($this->laravelVersion, '12.0', '>=')) {
            return ToolResponse::text('You are already on the latest version of Laravel');
        }

        if (version_compare($this->laravelVersion, '10.0', '<')) {
            return ToolResponse::text('We don\'t have a guide for upgrading to Laravel 10 right now. Please upgrade to Laravel 10 first.');
        }

        if (version_compare($this->laravelVersion, '11.0', '>=')) {
            return ToolResponse::text('We don\'t have a guide for upgrading to Laravel 12 right now. Please upgrade to Laravel 11 first.');
        }

        $guide = file_get_contents(realpath(__DIR__.'/../../docs/laravel/upgrade-guides/laravel-11.md'));

        return ToolResponse::text("Keep track of which steps you've completed and which you haven't. Here's the guide. <guide>$guide</guide>");
    }
}
