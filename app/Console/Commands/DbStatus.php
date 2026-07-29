<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quick overview of the database: connection, tables, row counts, and pending migrations.
 */
class DbStatus extends Command
{
    protected $signature = 'db:status';
    protected $description = 'Show database connection info, table row counts, and pending migrations';

    public function handle(): int
    {
        $this->info('=== Database Status ===');

        $driver = DB::connection()->getDriverName();
        $database = DB::connection()->getDatabaseName();
        $this->line("Driver: <comment>{$driver}</comment>");
        $this->line("Database: <comment>{$database}</comment>");

        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_' . $database;

        $rows = [];
        $totalRows = 0;

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            if (in_array($tableName, ['migrations'])) {
                continue;
            }

            $count = Schema::hasTable($tableName)
                ? DB::table($tableName)->count()
                : 0;
            $totalRows += $count;
            $rows[] = [$tableName, number_format($count)];
        }

        $this->newLine();
        $this->info('Table row counts:');
        $this->table(['Table', 'Rows'], $rows);
        $this->line("Total rows: <comment>" . number_format($totalRows) . "</comment>");

        // Pending migrations
        $ran = DB::table('migrations')->pluck('migration');
        $files = collect(glob(database_path('migrations/*.php')))
            ->map(fn ($path) => basename($path, '.php'))
            ->diff($ran);

        if ($files->isEmpty()) {
            $this->info('✅ No pending migrations.');
        } else {
            $this->warn('⚠ Pending migrations:');
            foreach ($files as $file) {
                $this->line(" - {$file}");
            }
        }

        return self::SUCCESS;
    }
}
