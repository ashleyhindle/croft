<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\ToolResponse;

class DatabaseQueryReadOnly extends DatabaseQueryReadWrite
{
    /**
     * List of allowed read-only SQL commands.
     */
    protected array $allowList = [
        'SELECT',
        'SHOW',
        'EXPLAIN',
        'DESCRIBE',
        'DESC',
        'WITH', // CTEs, but must be followed by SELECT
    ];

    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('Query the Database (read only)')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'eloquent_database_query';
    }

    public function getDescription(): string
    {
        return 'Query the database for a given SQL query. Very useful for data analysis and reporting.';
    }

    // TODO: How do we have a read only connection instead? This is brittle
    public function handle(array $arguments): ToolResponse
    {
        $query = trim($arguments['query']);
        $queryTrimmed = ltrim($query);
        $firstWord = strtoupper(strtok($queryTrimmed, " \t\n\r"));

        $isReadOnly = in_array($firstWord, $this->allowList, true);

        // Special handling for WITH (should be followed by SELECT)
        if ($firstWord === 'WITH') {
            if (! preg_match('/with\s+.*select\b/i', $queryTrimmed)) {
                $isReadOnly = false;
            }
        }

        if (! $isReadOnly) {
            return ToolResponse::error('Only read-only queries are allowed (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC, WITH SELECT).');
        }

        return parent::handle($arguments);
    }
}
