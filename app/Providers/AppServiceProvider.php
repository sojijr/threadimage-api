<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure SQLite database exists and has required tables
        if (config('database.default') === 'sqlite') {
            $this->ensureDatabaseExists();
            $this->ensureCacheTableExists();
        }
    }

    /**
     * Ensure the SQLite database file exists
     */
    private function ensureDatabaseExists(): void
    {
        $databasePath = database_path('database.sqlite');

        if (!file_exists(dirname($databasePath))) {
            mkdir(dirname($databasePath), 0755, true);
        }

        if (!file_exists($databasePath)) {
            touch($databasePath);
        }
    }

    /**
     * Ensure the cache table exists in the database
     */
    private function ensureCacheTableExists(): void
    {
        try {
            if (!Schema::hasTable('cache')) {
                Schema::create('cache', function ($table) {
                    $table->string('key')->primary();
                    $table->longText('value');
                    $table->integer('expiration');
                    $table->index('expiration');
                });
            }
        } catch (\Exception $e) {
            // If we can't create the table, fall back to file cache
            config(['cache.default' => 'file']);
        }
    }
}
