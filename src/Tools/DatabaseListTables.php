<?php

declare(strict_types=1);

namespace Croft\Tools;

use Croft\Feature\Tool\AbstractTool;
use Croft\Feature\Tool\ToolResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DatabaseListTables extends AbstractTool
{
    public function __construct()
    {
        // Setup annotations according to MCP specification
        $this->setTitle('List Database Tables')
            ->setReadOnly(true)        // Just listing commands, no modifications
            ->setDestructive(false)    // No destructive operations
            ->setIdempotent(true)      // Safe to retry
            ->setOpenWorld(false);     // Commands list is a closed world
    }

    public function getName(): string
    {
        return 'database_list_tables';
    }

    public function getDescription(): string
    {
        return 'List all schemas/tables in the database with their columns and types to get a full database structure and schema';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'filter' => [
                    'type' => 'string',
                    'description' => 'Filter the tables by name',
                ],
            ],
            'required' => [],
        ];
    }

    public function handle(array $arguments): ToolResponse
    {
        $structure = $this->getAllTablesStructure($arguments['filter'] ?? '');

        return ToolResponse::array(['engine' => DB::getDriverName(), 'structure' => $structure]);
    }

    public function getAllTables()
    {
        return Schema::getTables();
    }

    public function getTableStructure(string $tableName)
    {
        try {
            $columns = $this->getTableColumns($tableName);
            $indexes = $this->getTableIndexes($tableName);
            $foreignKeys = $this->getTableForeignKeys($tableName);

            return [
                'columns' => $columns,
                'indexes' => $indexes,
                'foreign_keys' => $foreignKeys,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get table structure for: ' . $tableName, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to notify the caller
        }
    }

    public function getAllTablesStructure(string $filter = '')
    {
        $structures = [];

        foreach ($this->getAllTables() as $table) {
            $tableName = $table['name'];

            // Skip tables that don't match the filter
            if ($filter && ! str_contains(strtolower($tableName), strtolower($filter))) {
                continue;
            }

            $structures[$tableName] = $this->getTableStructure($tableName);
        }

        return $structures;
    }

    protected function getTableColumns(string $tableName)
    {
        $columns = Schema::getColumnListing($tableName);
        $columnDetails = [];

        foreach ($columns as $column) {
            $columnDetails[$column] = [
                'type' => Schema::getColumnType($tableName, $column),
                'nullable' => DB::getSchemaBuilder()
                    ->getConnection()
                    ->getSchemaBuilder()
                    ->hasColumn($tableName, $column),
                'default' => '',
            ];
        }

        return $columnDetails;
    }

    protected function getTableIndexes(string $tableName)
    {
        try {
            $indexes = Schema::getIndexes($tableName);

            foreach ($indexes as $index) {
                $indexes[$index['name']] = [
                    'columns' => $index['columns'],
                    'type' => $index['type'],
                    'is_unique' => $index['unique'],
                    'is_primary' => $index['primary'],
                ];
            }

            return $indexes;
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getTableForeignKeys(string $tableName)
    {
        try {
            $foreignKeys = Schema::getForeignKeys($tableName);

            return $foreignKeys;
        } catch (\Exception $e) {
            return [];
        }
    }
}
