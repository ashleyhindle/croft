<?php

declare(strict_types=1);

namespace Croft\Commands;

use Illuminate\Console\Command;

class CroftCommand extends Command
{
    public $signature = 'croft {--debug}';

    public $description = 'Start your Croft MCP server';

    public function handle(): int
    {
        $debug = $this->option('debug');
        $server = new \Croft\Server(debug: $debug);
        $tools = config('croft.tools');

        // How to support artisan commands, not just our built in tools that are class based?
        foreach ($tools as $tool) {
            $server->tool(new $tool);
        }

        $resources = config('croft.resources');
        foreach ($resources as $resource) {
            $server->resource(new $resource);
        }

        $prompts = config('croft.prompts');
        foreach ($prompts as $prompt) {
            $server->prompt(new $prompt);
        }

        $server->run();

        return self::SUCCESS;
    }

    /**
     * Croft uses STDIO transport for MCP.
     *
     * So any messages for humans need to be written on STDERR.
     */
    /*private function out(string $message)
    {
        return fwrite(STDERR, $message);
    }*/
}
