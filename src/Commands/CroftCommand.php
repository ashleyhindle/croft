<?php

declare(strict_types=1);

namespace Croft\Commands;

use Illuminate\Console\Command;

class CroftCommand extends Command
{
    public $signature = 'croft';

    public $description = 'Start your Croft MCP server';

    public function handle(): int
    {
        // $this->out('Starting Croft MCP server...');
        $server = new \Croft\Server;
        $tools = config('croft.tools');
        // How to support artisan commands, not just our built in tools that are class based?
        foreach ($tools as $tool) {
            $server->tool(new $tool);
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
