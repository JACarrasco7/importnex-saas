<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Clears all Laravel caches (config, route, view, application).
 */
class CacheClearAll extends Command
{
    protected $signature = 'cache:clear-all';
    protected $description = 'Clear all Laravel caches (config, routes, views, app)';

    public function handle(): int
    {
        $this->info('Clearing all caches...');

        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        $this->call('optimize:clear');

        $this->newLine();
        $this->info('✅ All caches cleared.');

        return self::SUCCESS;
    }
}
