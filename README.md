![Croft Laravel](https://github.com/user-attachments/assets/6c42134d-5236-4ae1-8c61-bce4db5d9de1)

Croft is an MCP server designed specifcally for Laravel developers, by Laravel developers. We wanted a plug and play solution to boosting productivity, so we built one. The `php artisan croft` command provides tools to your MCP client to help your AI pair programmer work better. This package is specifically designed to offer tools that are useful _locally_.

Add even more functionality with [usecroft.com »](https://usecroft.com) hosted servers.

## Installation

Install the package via composer:

```bash
composer require usecroft/laravel --dev
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="croft-config"
```

Add to your IDE:
```bash
php artisan croft:install
```

Add more functionality with [usecroft.com »](https://usecroft.com) hosted servers (coming soon)

## Usage

To make use of Croft you need to add it as an MCP server in your favourite tool.

The command the MCP client needs to run is `./artisan croft`

**Cursor** ([Docs](https://docs.cursor.com/context/model-context-protocol#configuring-mcp-servers))

We recommend you ship an `mcp.json` file with your project in `.cursor/mcp.json`

```json
{
  "mcpServers": {
    "croft": {
      "command": "./artisan",
      "args": ["croft"]
    }
  }
}
```

## Current functionality

- Screenshot URLs
- Query database (read only, or read write)
- Get absolute URL from relative path
- Get current date and time
- Read last X log entries
- Read & filter database structure - tables, columns, indexes, foreign keys
- List/filter routes
- List artisan commands
- List available config() keys (and optionally values) in dot notation
- List available env() keys (without leaking secrets of course)

## Extra functionality

Add more functionality with [usecroft.com »](https://usecroft.com) remote MCP servers (coming soon).

## Add your own tools

It's trivial to add your own tools.

Just create a class that extends our `Croft\Feature\Tool\AbstractTool` class, then make sure it's in your `croft.php` config file.

Example:

```php
<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;

class {{CLASSNAME}} extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('{{NAME}}')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true);     // Safe to retry
    }

    public function getName(): string
    {
        return '{{NAME}}';
    }

    public function getDescription(): string
    {
        return 'Must explain well what the tool can do so the MCP client can decide when to use it.';
    }

    /**
    * What params does the MCP client need to provide to use this tool?
    **/
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
            ],
            'required' => [],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        return ToolResponse::text("Howdy, this is the start of something great.");
    }
}
```

After adding a tool you'll need to restart the server, or ask the MCP client to relist the tools.

## Support & Credits

Croft was developed by [Ashley Hindle](https://ashleyhindle.com) with support from [Springloaded](https://springloaded.co). If you like it, please star it, share it, and let us know!

**Ashley Hindle**
- https://bsky.app/profile/ashleyhindle.com
- https://twitter.com/ashleyhindle
- https://ashleyhindle.com

**Springloaded**
- https://bsky.app/profile/springloaded.co
- https://twitter.com/ashleyhindle
- https://springloaded.co
