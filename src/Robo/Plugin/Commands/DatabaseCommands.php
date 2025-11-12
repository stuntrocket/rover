<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Database management commands for Laravel projects
 */
class DatabaseCommands extends BaseCommand
{
    /**
     * Fresh database migration with seeding
     *
     * Drops all tables, runs migrations, and seeds the database.
     *
     * @command rover:fresh
     * @aliases fresh
     * @option $seed Run database seeders after migration
     * @option $force Force the operation without confirmation
     */
    public function fresh(array $options = ['seed' => true, 'force' => false]): Result|ResultData
    {
        $this->requireLaravelProject();

        // Safety check for production
        if (!$options['force'] && !$this->isLocalEnvironment()) {
            $this->error('This command cannot be run in production!');
            $this->warning('Use --force flag only if you absolutely know what you\'re doing.');
            return new ResultData(1, "");
        }

        $this->warning('This will DROP ALL TABLES and re-run migrations!');

        if (!$options['force']) {
            if (!$this->io()->confirm('Are you sure you want to continue?', false)) {
                $this->info('Operation cancelled.');
                return Result::cancelled();
            }
        }

        $this->info('Dropping all tables and running fresh migrations...');

        // Run migrate:fresh
        $migrateOptions = ['force' => true];
        if ($options['seed']) {
            $migrateOptions['seed'] = true;
        }

        $result = $this->artisan('migrate:fresh', $migrateOptions);

        if (!$result->wasSuccessful()) {
            $this->error('Migration failed!');
            return new ResultData(1, "");
        }

        if ($options['seed']) {
            $this->success('Database migrated and seeded successfully!');
        } else {
            $this->success('Database migrated successfully!');
        }

        return new ResultData(0, "");
    }

    /**
     * Reset database (rollback all migrations and re-migrate)
     *
     * @command rover:db:reset
     * @option $seed Run database seeders after migration
     * @option $force Force the operation without confirmation
     */
    public function reset(array $options = ['seed' => false, 'force' => false]): Result|ResultData
    {
        $this->requireLaravelProject();

        if (!$options['force'] && !$this->isLocalEnvironment()) {
            $this->error('This command cannot be run in production!');
            return new ResultData(1, "");
        }

        $this->info('Resetting database...');

        // Rollback all migrations
        $this->say('Rolling back migrations...');
        $rollbackResult = $this->artisan('migrate:rollback', ['step' => 999, 'force' => true]);

        if (!$rollbackResult->wasSuccessful()) {
            $this->error('Rollback failed!');
            return new ResultData(1, "");
        }

        // Run migrations
        $this->say('Running migrations...');
        $migrateResult = $this->artisan('migrate', ['force' => true]);

        if (!$migrateResult->wasSuccessful()) {
            $this->error('Migration failed!');
            return new ResultData(1, "");
        }

        // Seed if requested
        if ($options['seed']) {
            $this->say('Seeding database...');
            $seedResult = $this->artisan('db:seed', ['force' => true]);

            if (!$seedResult->wasSuccessful()) {
                $this->error('Seeding failed!');
                return new ResultData(1, "");
            }
        }

        $this->success('Database reset successfully!');
        return new ResultData(0, "");
    }

    /**
     * Run database seeders
     *
     * @command rover:db:seed
     * @option $class The class name of the seeder
     * @option $force Force the operation without confirmation
     */
    public function seed(array $options = ['class' => null, 'force' => false]): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Running database seeders...');

        $seedOptions = ['force' => true];
        if ($options['class']) {
            $seedOptions['class'] = $options['class'];
            $this->say("Running seeder: {$options['class']}");
        }

        $result = $this->artisan('db:seed', $seedOptions);

        if (!$result->wasSuccessful()) {
            $this->error('Seeding failed!');
            return new ResultData(1, "");
        }

        $this->success('Database seeded successfully!');
        return new ResultData(0, "");
    }

    /**
     * Show database migration status
     *
     * @command rover:db:status
     */
    public function status(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Database migration status:');
        return $this->artisan('migrate:status');
    }

    /**
     * Check if we're in a local environment
     *
     * @return bool
     */
    protected function isLocalEnvironment(): bool
    {
        if (!file_exists('.env')) {
            return true; // Assume local if no .env
        }

        $env = file_get_contents('.env');

        // Check for APP_ENV
        if (preg_match('/^APP_ENV=(.*)$/m', $env, $matches)) {
            $appEnv = trim($matches[1]);
            return in_array($appEnv, ['local', 'development', 'dev', 'testing']);
        }

        return true; // Default to allowing if can't determine
    }
}
