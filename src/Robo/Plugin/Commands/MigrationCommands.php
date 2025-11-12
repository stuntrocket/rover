<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Migration safety and management commands
 */
class MigrationCommands extends BaseCommand
{
    /**
     * Check for migration conflicts
     *
     * @command rover:migrate:check
     */
    public function check(): Result
    {
        $this->requireLaravelProject();

        $this->info('Checking for migration conflicts...');

        $migrations = glob('database/migrations/*.php');

        if (empty($migrations)) {
            $this->warning('No migrations found');
            return Result::success($this);
        }

        $conflicts = [];
        $timestamps = [];

        // Check for duplicate timestamps
        foreach ($migrations as $migration) {
            $filename = basename($migration);

            // Extract timestamp (first 17 characters: YYYY_MM_DD_HHMMSS)
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_/', $filename, $matches)) {
                $timestamp = $matches[1];

                if (isset($timestamps[$timestamp])) {
                    $conflicts[] = [
                        'timestamp' => $timestamp,
                        'files' => [$timestamps[$timestamp], $filename],
                    ];
                } else {
                    $timestamps[$timestamp] = $filename;
                }
            }
        }

        // Check for naming conflicts
        $names = [];
        foreach ($migrations as $migration) {
            $filename = basename($migration);
            $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);

            if (isset($names[$name])) {
                $this->warning("Duplicate migration name: $name");
            }
            $names[$name] = $filename;
        }

        if (empty($conflicts)) {
            $this->success('✓ No migration conflicts found!');
        } else {
            $this->error('⚠ Migration conflicts detected:');
            foreach ($conflicts as $conflict) {
                $this->say("\nTimestamp: {$conflict['timestamp']}");
                foreach ($conflict['files'] as $file) {
                    $this->say("  - $file");
                }
            }

            $this->info("\nResolve conflicts by:");
            $this->say("1. Re-creating one migration with: php artisan make:migration");
            $this->say("2. Copying the migration code to the new file");
            $this->say("3. Deleting the conflicting migration");

            return Result::error($this);
        }

        return Result::success($this);
    }

    /**
     * Safe migration rollback with preview
     *
     * @command rover:migrate:rollback-safe
     * @option $step Number of migrations to rollback
     * @option $force Skip confirmation
     */
    public function rollbackSafe(array $options = ['step' => 1, 'force' => false]): Result
    {
        $this->requireLaravelProject();

        // Safety check
        if (!$this->isLocalEnvironment() && !$options['force']) {
            $this->error('Cannot rollback in production!');
            $this->warning('Use --force only if you are absolutely certain.');
            return Result::error($this);
        }

        $this->info('Checking which migrations will be rolled back...');

        // Show what will be rolled back
        $result = $this->artisan('migrate:status');

        if (!$result->wasSuccessful()) {
            $this->error('Could not get migration status');
            return Result::error($this);
        }

        $this->say('');

        if (!$options['force']) {
            $this->warning("This will rollback {$options['step']} migration(s)!");
            if (!$this->io()->confirm('Continue?', false)) {
                return Result::cancelled();
            }
        }

        // Perform rollback
        $this->info('Rolling back migrations...');
        $rollbackResult = $this->artisan('migrate:rollback', ['step' => $options['step']]);

        if ($rollbackResult->wasSuccessful()) {
            $this->success('✓ Rollback complete!');
        } else {
            $this->error('Rollback failed!');
        }

        return $rollbackResult;
    }

    /**
     * Show migration history and status
     *
     * @command rover:migrate:history
     */
    public function history(): Result
    {
        $this->requireLaravelProject();

        $this->info('Migration History:');
        $this->say('');

        // Show migration status
        $this->artisan('migrate:status');

        // Show pending migrations
        $this->say('');
        $this->info('Pending migrations:');

        $migrations = glob('database/migrations/*.php');
        $ran = $this->getRanMigrations();

        $pending = [];
        foreach ($migrations as $migration) {
            $filename = basename($migration, '.php');
            if (!in_array($filename, $ran)) {
                $pending[] = $filename;
            }
        }

        if (empty($pending)) {
            $this->say('  None');
        } else {
            foreach ($pending as $migration) {
                $this->say("  - $migration");
            }
        }

        return Result::success($this);
    }

    /**
     * Verify migrations are safe to run
     *
     * @command rover:migrate:verify
     */
    public function verify(): Result
    {
        $this->requireLaravelProject();

        $this->info('Verifying migrations...');

        $issues = [];

        // Check for conflicts
        $this->say('Checking for conflicts...');
        $checkResult = $this->check();

        if (!$checkResult->wasSuccessful()) {
            $issues[] = 'Migration conflicts detected';
        } else {
            $this->say('  ✓ No conflicts');
        }

        // Check for risky operations
        $this->say('');
        $this->say('Checking for risky operations...');

        $migrations = glob('database/migrations/*.php');
        $ran = $this->getRanMigrations();

        $riskyOperations = [
            'dropColumn' => 'Dropping columns',
            'drop(' => 'Dropping tables',
            'dropIfExists' => 'Dropping tables',
        ];

        $foundRisky = false;

        foreach ($migrations as $migration) {
            $filename = basename($migration, '.php');

            // Only check pending migrations
            if (in_array($filename, $ran)) {
                continue;
            }

            $content = file_get_contents($migration);

            foreach ($riskyOperations as $pattern => $description) {
                if (strpos($content, $pattern) !== false) {
                    $this->warning("  ⚠ $filename: $description");
                    $foundRisky = true;
                }
            }
        }

        if (!$foundRisky) {
            $this->say('  ✓ No risky operations found');
        }

        // Summary
        $this->say('');

        if (empty($issues) && !$foundRisky) {
            $this->success('✓ Migrations are safe to run!');
            return Result::success($this);
        } else {
            $this->warning('⚠ Review warnings before running migrations');
            $this->info('Run with: php artisan migrate --pretend to preview SQL');
            return Result::error($this);
        }
    }

    /**
     * Create a migration with conflict checking
     *
     * @command rover:make:migration
     * @param string $name Migration name
     * @option $table The table to be created/modified
     * @option $create Create table migration
     */
    public function makeMigration(string $name, array $options = ['table' => null, 'create' => null]): Result
    {
        $this->requireLaravelProject();

        // Check for naming conflicts first
        $migrations = glob('database/migrations/*_' . $name . '.php');

        if (!empty($migrations)) {
            $this->warning('A migration with this name already exists:');
            foreach ($migrations as $migration) {
                $this->say('  - ' . basename($migration));
            }

            if (!$this->io()->confirm('Create anyway?', false)) {
                return Result::cancelled();
            }
        }

        // Build artisan command
        $command = "make:migration $name";

        if ($options['table']) {
            $command .= " --table={$options['table']}";
        }

        if ($options['create']) {
            $command .= " --create={$options['create']}";
        }

        $result = $this->artisan($command);

        if ($result->wasSuccessful()) {
            $this->success('✓ Migration created!');

            // Check for conflicts after creation
            $this->say('');
            $this->check();
        }

        return $result;
    }

    /**
     * Get list of ran migrations
     */
    protected function getRanMigrations(): array
    {
        // Try to read from migrations table
        $result = shell_exec('php artisan db:table migrations 2>/dev/null');

        if (!$result) {
            return [];
        }

        $ran = [];
        $lines = explode("\n", $result);

        foreach ($lines as $line) {
            if (preg_match('/^\|\s+\d+\s+\|\s+([^\|]+)\s+\|/', $line, $matches)) {
                $ran[] = trim($matches[1]);
            }
        }

        return $ran;
    }

    /**
     * Check if in local environment
     */
    protected function isLocalEnvironment(): bool
    {
        if (!file_exists('.env')) {
            return true;
        }

        $env = file_get_contents('.env');

        if (preg_match('/^APP_ENV=(.*)$/m', $env, $matches)) {
            $appEnv = trim($matches[1], '"\'');
            return in_array($appEnv, ['local', 'development', 'dev', 'testing']);
        }

        return true;
    }
}
