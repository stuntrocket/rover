<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Data management and anonymization commands
 */
class DataCommands extends BaseCommand
{
    /**
     * Create a quick database snapshot
     *
     * @command rover:db:snapshot
     * @aliases snapshot
     */
    public function snapshot(): Result
    {
        $this->requireLaravelProject();

        $this->info('Creating database snapshot...');

        // Create snapshot directory
        $snapshotDir = 'storage/backups/snapshots';
        if (!is_dir($snapshotDir)) {
            mkdir($snapshotDir, 0755, true);
        }

        // Use a simple "latest" filename for quick snapshots
        $dbConfig = $this->getDatabaseConfig();
        $filename = "latest_{$dbConfig['database']}.sql.gz";
        $snapshotPath = "$snapshotDir/$filename";

        // Create backup
        $result = $this->createBackup($dbConfig, $snapshotPath, true);

        if ($result->wasSuccessful()) {
            $this->success('✓ Snapshot created!');
            $this->info('Restore with: rover:db:snapshot:restore');
            return new ResultData(0, "");
        } else {
            $this->error('Snapshot failed!');
            return new ResultData(1, "");
        }
    }

    /**
     * Restore from latest snapshot
     *
     * @command rover:db:snapshot:restore
     * @option $force Skip confirmation
     */
    public function snapshotRestore(array $options = ['force' => false]): Result
    {
        $this->requireLaravelProject();

        $snapshotDir = 'storage/backups/snapshots';
        $dbConfig = $this->getDatabaseConfig();
        $snapshotPath = "$snapshotDir/latest_{$dbConfig['database']}.sql.gz";

        if (!file_exists($snapshotPath)) {
            $this->error('No snapshot found! Create one with: rover:db:snapshot');
            return new ResultData(1, "");
        }

        if (!$options['force']) {
            $this->warning('This will replace your current database with the snapshot!');
            if (!$this->io()->confirm('Continue?', false)) {
                return Result::cancelled();
            }
        }

        $this->info('Restoring from snapshot...');

        $result = $this->restoreBackup($dbConfig, $snapshotPath);

        if ($result->wasSuccessful()) {
            $this->success('✓ Snapshot restored!');
            return new ResultData(0, "");
        } else {
            $this->error('Restore failed!');
            return new ResultData(1, "");
        }
    }

    /**
     * Anonymize sensitive data in database
     *
     * @command rover:db:anonymize
     * @option $force Skip confirmation
     */
    public function anonymize(array $options = ['force' => false]): Result
    {
        $this->requireLaravelProject();

        // Safety check
        if (!$this->isLocalEnvironment() && !$options['force']) {
            $this->error('Cannot anonymize in production environment!');
            $this->warning('Use --force only if you are absolutely certain.');
            return new ResultData(1, "");
        }

        if (!$options['force']) {
            $this->warning('This will ANONYMIZE user data in your database!');
            $this->info('This is useful for creating safe development/staging data.');
            if (!$this->io()->confirm('Continue?', false)) {
                return Result::cancelled();
            }
        }

        $this->info('Anonymizing database...');

        // Create anonymization SQL
        $sql = $this->generateAnonymizationSQL();

        // Execute anonymization
        $this->say('Anonymizing users table...');
        $result = $this->artisan('db:statement', ['statement' => $sql['users']]);

        if ($result->wasSuccessful()) {
            $this->success('✓ Data anonymized successfully!');
            $this->info('Users now have generic emails like user1@example.com');
            return new ResultData(0, "");
        } else {
            $this->error('Anonymization failed!');
            return new ResultData(1, "");
        }
    }

    /**
     * Sync database from remote environment
     *
     * @command rover:db:sync
     * @param string $environment Environment to sync from (staging, production)
     * @option $anonymize Anonymize data after sync
     */
    public function sync(string $environment, array $options = ['anonymize' => true]): Result
    {
        $this->requireLaravelProject();

        if (!in_array($environment, ['staging', 'production'])) {
            $this->error('Invalid environment. Use: staging or production');
            return new ResultData(1, "");
        }

        // Safety check
        if (!$this->isLocalEnvironment()) {
            $this->error('Can only sync TO local environment!');
            return new ResultData(1, "");
        }

        $this->warning("This will replace your local database with data from $environment!");
        if (!$this->io()->confirm('Continue?', false)) {
            return Result::cancelled();
        }

        $this->info("Syncing database from $environment...");

        // This is a placeholder - actual implementation would depend on:
        // - SSH access to remote server
        // - Database credentials for remote
        // - Or Laravel Vapor/Forge API integration

        $this->warning('Database sync requires configuration:');
        $this->say('1. Add remote database credentials to rover.yml');
        $this->say('2. Or use Laravel Forge/Vapor integration');
        $this->say('3. Or manually export/import via SSH');

        $this->info("\nManual sync example:");
        $this->say("  # On remote server:");
        $this->say("  mysqldump -u user -p database > backup.sql");
        $this->say("  ");
        $this->say("  # On local machine:");
        $this->say("  scp user@remote:/path/backup.sql .");
        $this->say("  mysql -u root database < backup.sql");

        if ($options['anonymize']) {
            $this->info("\nDon't forget to anonymize after sync:");
            $this->say("  vendor/bin/robo rover:db:anonymize");
        }

        return new ResultData(0, "");
    }

    /**
     * Generate anonymization SQL
     */
    protected function generateAnonymizationSQL(): array
    {
        return [
            'users' => "
                UPDATE users
                SET
                    email = CONCAT('user', id, '@example.com'),
                    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                    remember_token = NULL,
                    name = CONCAT('User ', id)
                WHERE id > 1
            ",
        ];
    }

    /**
     * Get database configuration (reuse from DatabaseBackupCommands logic)
     */
    protected function getDatabaseConfig(): ?array
    {
        if (!file_exists('.env')) {
            return null;
        }

        $env = file_get_contents('.env');
        $config = [];

        $patterns = [
            'connection' => '/^DB_CONNECTION=(.*)$/m',
            'host' => '/^DB_HOST=(.*)$/m',
            'port' => '/^DB_PORT=(.*)$/m',
            'database' => '/^DB_DATABASE=(.*)$/m',
            'username' => '/^DB_USERNAME=(.*)$/m',
            'password' => '/^DB_PASSWORD=(.*)$/m',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $env, $matches)) {
                $config[$key] = trim($matches[1], '"\'');
            }
        }

        return !empty($config) ? $config : null;
    }

    /**
     * Create backup helper (simplified version)
     */
    protected function createBackup(array $config, string $backupPath, bool $compress): Result
    {
        $connection = $config['connection'] ?? 'mysql';

        if ($connection === 'mysql' || $connection === 'mariadb') {
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s %s %s | gzip > %s',
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                $config['password'] ? '-p' . escapeshellarg($config['password']) : '',
                escapeshellarg($config['database']),
                escapeshellarg($backupPath)
            );

            return $this->taskExec($command)->run();
        }

        $this->error("Unsupported database: $connection");
        return new ResultData(1, "");
    }

    /**
     * Restore backup helper
     */
    protected function restoreBackup(array $config, string $backupPath): Result
    {
        $connection = $config['connection'] ?? 'mysql';

        if ($connection === 'mysql' || $connection === 'mariadb') {
            $command = sprintf(
                'gunzip -c %s | mysql -h%s -P%s -u%s %s %s',
                escapeshellarg($backupPath),
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                $config['password'] ? '-p' . escapeshellarg($config['password']) : '',
                escapeshellarg($config['database'])
            );

            return $this->taskExec($command)->run();
        }

        $this->error("Unsupported database: $connection");
        return new ResultData(1, "");
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
