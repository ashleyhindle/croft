### MCP server designed specifically for Laravel developers
Artisan command helps your AI pair programmer work better.

<p align="center">
<img src="croft-logo3.png" width=180 height=180/>
</p>


![](docs-terminal.png)


## Installation

Install the package via composer:

```bash
composer require ashleyhindle/croft --dev
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="croft-config"
```

## Usage
To make use of Croft you need to add it as an MCP server in your favourite tool.

The command the MCP client needs to run it `./artisan croft`

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
- Get Flux UI component details
- Read last X log entries
- Read & filter database structure - tables, columns, indexes, foreign keys
- List/filter routes
- List artisan commands
- List available config() keys in dot notation
- List available env() keys (without leaking secrets of course)

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

This was developed by Ashley Hindle. If you like it, please star it, share it, and let me know!

- [Bluesky](https://bsky.app/profile/ashleyhindle.com)
- [Twitter](https://twitter.com/ashleyhindle)
- Website [https://ashleyhindle.com](https://ashleyhindle.com)
