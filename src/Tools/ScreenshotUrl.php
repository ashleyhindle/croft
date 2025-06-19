<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\Config;
use Spatie\Browsershot\Browsershot;

class ScreenshotUrl extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Take Screenshot of Relative Path')
            ->setReadOnly(true)
            ->setDestructive(false)
            ->setIdempotent(true)
            ->setOpenWorld(true); // Still accesses external *resolved* URLs
    }

    public function getName(): string
    {
        return 'screenshot_path'; // Renaming slightly to reflect input
    }

    public function getDescription(): string
    {
        return 'Takes a screenshot of a given relative application path (e.g., "/users/1"). Prepends config(\'app.url\') to the path. Ignores HTTPS errors. Requires spatie/browsershot package and its dependencies (Node, Puppeteer) to be installed.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'path' => [
                    'type' => 'string',
                    'description' => 'The relative application path to take a screenshot of (e.g., /users, /posts/1).',
                ],
                'width' => [
                    'type' => 'integer',
                    'description' => 'The browser window width in pixels.',
                    'default' => 1512,
                ],
                'height' => [
                    'type' => 'integer',
                    'description' => 'The browser window height in pixels.',
                    'default' => 982,
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function shouldRegister(): bool
    {
        try {
            // @phpstan-ignore class.notFound
            Browsershot::url('https://example.com');
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    public function handle(array $arguments): ToolResponse
    {
        // Check if the suggested package class exists
        try {
            // @phpstan-ignore class.notFound
            Browsershot::url('https://example.com');
        } catch (\Throwable $e) {
            return ToolResponse::error(
                'Browsershot package not installed or configured correctly. Please run "composer require spatie/browsershot ; npm install puppeteer" and ensure Node/Puppeteer are installed to use this tool. Error: '.$e->getMessage()
            );
        }

        $relativePath = $arguments['path'];
        $width = $arguments['width'] ?? 1512;
        $height = $arguments['height'] ?? 982;

        // Get base URL from config
        $baseUrl = Config::get('app.url');
        if (empty($baseUrl)) {
            return ToolResponse::error('Application base URL (APP_URL or config(\'app.url\')) is not configured.');
        }

        // Ensure path starts with a slash
        if (! str_starts_with($relativePath, '/')) {
            $relativePath = '/'.$relativePath;
        }

        // Construct the full URL
        $fullUrl = rtrim($baseUrl, '/').$relativePath;

        // Basic URL validation (on the constructed URL)
        if (! filter_var($fullUrl, FILTER_VALIDATE_URL)) {
            return ToolResponse::error("Constructed URL '{$fullUrl}' is invalid.");
        }

        try {
            // @phpstan-ignore class.notFound
            $base64Data = Browsershot::url($fullUrl)
                ->windowSize($width, $height)
                ->ignoreHttpsErrors()
                ->waitUntilNetworkIdle(false)
                ->base64Screenshot();

            return ToolResponse::image($base64Data, 'image/png');
        } catch (\Throwable $e) {
            return ToolResponse::error("Screenshot failed for path '{$relativePath}': ".$e->getMessage());
        }
    }
}
