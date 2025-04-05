<?php

declare(strict_types=1);

namespace Croft\Commands;

use Illuminate\Console\Command;

class CroftRestartCommand extends Command
{
    public $signature = 'croft:restart';

    public $description = 'Restart your Croft MCP server';

    public function handle(): int
    {
        $this->out('Restarting Croft MCP server...');

        return self::SUCCESS;
    }

    /**
     * Croft uses STDIO transport for MCP.
     *
     * So any messages for humans need to be written on STDERR.
     */
    private function out(string $message)
    {
        return fwrite(STDERR, $message);
    }
}
