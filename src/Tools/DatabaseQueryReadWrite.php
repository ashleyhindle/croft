<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\DB;

class DatabaseQueryReadWrite extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Query the Database (read write)')
            ->setReadOnly(false)        // Just listing commands, no modifications
            ->setDestructive(true)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'eloquent_database_query_read_write';
    }

    public function getDescription(): string
    {
        return 'Query the database for a given SQL query. Very useful for data analysis, reporting, and updates.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'query' => [
                    'type' => 'string',
                    'description' => 'The SQL query to execute.',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        try {
            $results = DB::select($arguments['query']);
        } catch (\Exception $e) {
            return ToolResponse::error('Error executing query: '.$e->getMessage());
        }

        return ToolResponse::array([
            'results' => $results,
        ]);
    }
}
