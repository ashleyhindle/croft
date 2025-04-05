#  MCP server for all of your Laravel projects - better AI pair programming, coming soon..

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ashleyhindle/croft.svg?style=flat-square)](https://packagist.org/packages/ashleyhindle/croft)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ashleyhindle/croft/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ashleyhindle/croft/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ashleyhindle/croft/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ashleyhindle/croft/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.


## Installation

Install the package via composer:

```bash
composer require ashleyhindle/croft
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="croft-config"
```

## Usage
To make use of Croft you need to add it as an MCP server in your favourite tool.

The command the MCP client needs to run it `./artisan croft`

**Cursor** ([Docs](https://docs.cursor.com/context/model-context-protocol#configuring-mcp-servers))
Recommend mcp.json file shipped with the project: `.cursor/mcp.json`
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

## Support & Credits

This was developed by Ashley Hindle. If you like it, please star it, share it, and let me know!

- [Bluesky](https://bsky.app/profile/ashleyhindle.com)
- [Twitter](https://twitter.com/ashleyhindle)
- Website [https://ashleyhindle.com](https://ashleyhindle.com)
