<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Database backup and restore commands
 */
class DatabaseBackupCommands extends BaseCommand
{
    /**
     * Create a database backup
     *
     * @command rover:db:backup
     * @option $name Custom name for backup (defaults to timestamp)
     * @option $compress Compress backup with gzip
     */
    public function backup(array $options = ['name' => null, 'compress' => true]): Result
    {
        $this->requireLaravelProject();

        if (!file_exists('.env')) {
            $this->error('.env file not found!');
            return Result::error($this);
        }

        $this->info('Creating database backup...');

        // Get database configuration
        $dbConfig = $this->getDatabaseConfig();

        if (!$dbConfig) {
            $this->error('Could not read database configuration from .env');
            return Result::error($this);
        }

        // Create backup directory
        $backupDir = 'storage/backups/database';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Generate backup filename
        $timestamp = date('Y-m-d_His');
        $name = $options['name'] ?? $timestamp;
        $filename = "{$name}_{$dbConfig['database']}.sql";

        if ($options['compress']) {
            $filename .= '.gz';
        }

        $backupPath = "$backupDir/$filename";

        // Create backup based on database type
        $result = $this->createBackup($dbConfig, $backupPath, $options['compress']);

        if ($result->wasSuccessful()) {
            $size = $this->formatBytes(filesize($backupPath));
            $this->success("✓ Backup created: $backupPath ($size)");

            // Rotate old backups
            $this->rotateBackups($backupDir);

            return Result::success($this);
        } else {
            $this->error('Backup failed!');
            return Result::error($this);
        }
    }

    /**
     * List available database backups
     *
     * @command rover:db:backups
     */
    public function listBackups(): Result
    {
        $this->requireLaravelProject();

        $backupDir = 'storage/backups/database';

        if (!is_dir($backupDir)) {
            $this->warning('No backups found. Run rover:db:backup to create one.');
            return Result::success($this);
        }

        $backups = glob("$backupDir/*.sql*");

        if (empty($backups)) {
            $this->warning('No backups found.');
            return Result::success($this);
        }

        // Sort by modification time (newest first)
        usort($backups, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $this->info('Available database backups:');
        $this->say('');

        foreach ($backups as $index => $backup) {
            $filename = basename($backup);
            $size = $this->formatBytes(filesize($backup));
            $date = date('Y-m-d H:i:s', filemtime($backup));

            $this->say(($index + 1) . ". $filename");
            $this->say("   Size: $size");
            $this->say("   Date: $date");
            $this->say('');
        }

        return Result::success($this);
    }

    /**
     * Restore database from backup
     *
     * @command rover:db:restore
     * @param string|null $backup Backup filename or number from list
     * @option $force Skip confirmation prompt
     */
    public function restore(?string $backup = null, array $options = ['force' => false]): Result
    {
        $this->requireLaravelProject();

        $backupDir = 'storage/backups/database';

        if (!is_dir($backupDir)) {
            $this->error('No backups directory found!');
            return Result::error($this);
        }

        $backups = glob("$backupDir/*.sql*");

        if (empty($backups)) {
            $this->error('No backups found!');
            return Result::error($this);
        }

        // Sort by modification time (newest first)
        usort($backups, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Select backup
        if (!$backup) {
            // Show list and prompt for selection
            $this->info('Available backups:');
            foreach ($backups as $index => $file) {
                $filename = basename($file);
                $date = date('Y-m-d H:i:s', filemtime($file));
                $this->say(($index + 1) . ". $filename ($date)");
            }

            $selection = $this->io()->ask('Select backup number');
            $index = (int)$selection - 1;

            if (!isset($backups[$index])) {
                $this->error('Invalid selection');
                return Result::error($this);
            }

            $backupPath = $backups[$index];
        } elseif (is_numeric($backup)) {
            // Backup number provided
            $index = (int)$backup - 1;
            if (!isset($backups[$index])) {
                $this->error('Invalid backup number');
                return Result::error($this);
            }
            $backupPath = $backups[$index];
        } else {
            // Backup filename provided
            $backupPath = "$backupDir/$backup";
            if (!file_exists($backupPath)) {
                $this->error("Backup not found: $backup");
                return Result::error($this);
            }
        }

        // Confirm restoration
        if (!$options['force']) {
            $this->warning('This will REPLACE your current database!');
            if (!$this->io()->confirm('Are you sure?', false)) {
                return Result::cancelled();
            }
        }

        $this->info('Restoring database from: ' . basename($backupPath));

        // Get database configuration
        $dbConfig = $this->getDatabaseConfig();

        if (!$dbConfig) {
            $this->error('Could not read database configuration');
            return Result::error($this);
        }

        // Restore backup
        $result = $this->restoreBackup($dbConfig, $backupPath);

        if ($result->wasSuccessful()) {
            $this->success('✓ Database restored successfully!');
            return Result::success($this);
        } else {
            $this->error('Restore failed!');
            return Result::error($this);
        }
    }

    /**
     * Delete old database backups
     *
     * @command rover:db:backup:clean
     * @option $keep Number of backups to keep (default: 7)
     * @option $force Skip confirmation
     */
    public function cleanBackups(array $options = ['keep' => 7, 'force' => false]): Result
    {
        $this->requireLaravelProject();

        $backupDir = 'storage/backups/database';

        if (!is_dir($backupDir)) {
            $this->info('No backups directory found.');
            return Result::success($this);
        }

        $backups = glob("$backupDir/*.sql*");

        if (empty($backups)) {
            $this->info('No backups found.');
            return Result::success($this);
        }

        // Sort by modification time (oldest first)
        usort($backups, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        $keep = (int)$options['keep'];
        $toDelete = array_slice($backups, 0, -$keep);

        if (empty($toDelete)) {
            $this->info('No backups to clean up.');
            return Result::success($this);
        }

        $this->warning('Will delete ' . count($toDelete) . ' old backup(s)');

        if (!$options['force']) {
            if (!$this->io()->confirm('Continue?', false)) {
                return Result::cancelled();
            }
        }

        foreach ($toDelete as $backup) {
            unlink($backup);
            $this->say('Deleted: ' . basename($backup));
        }

        $this->success('✓ Cleanup complete. Kept ' . $keep . ' most recent backup(s).');

        return Result::success($this);
    }

    /**
     * Create a database backup
     *
     * @param array $config
     * @param string $backupPath
     * @param bool $compress
     * @return Result
     */
    protected function createBackup(array $config, string $backupPath, bool $compress): Result
    {
        $connection = $config['connection'] ?? 'mysql';

        switch ($connection) {
            case 'mysql':
            case 'mariadb':
                return $this->backupMySQL($config, $backupPath, $compress);

            case 'pgsql':
                return $this->backupPostgreSQL($config, $backupPath, $compress);

            case 'sqlite':
                return $this->backupSQLite($config, $backupPath, $compress);

            default:
                $this->error("Unsupported database connection: $connection");
                return Result::error($this);
        }
    }

    /**
     * Backup MySQL database
     */
    protected function backupMySQL(array $config, string $backupPath, bool $compress): Result
    {
        $command = sprintf(
            'mysqldump -h%s -P%s -u%s %s %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            $config['password'] ? '-p' . escapeshellarg($config['password']) : '',
            escapeshellarg($config['database'])
        );

        if ($compress) {
            $command .= ' | gzip';
        }

        $command .= ' > ' . escapeshellarg($backupPath);

        return $this->taskExec($command)->run();
    }

    /**
     * Backup PostgreSQL database
     */
    protected function backupPostgreSQL(array $config, string $backupPath, bool $compress): Result
    {
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s',
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database'])
        );

        if ($compress) {
            $command .= ' | gzip';
        }

        $command .= ' > ' . escapeshellarg($backupPath);

        return $this->taskExec($command)->run();
    }

    /**
     * Backup SQLite database
     */
    protected function backupSQLite(array $config, string $backupPath, bool $compress): Result
    {
        $dbPath = $config['database'];

        if (!file_exists($dbPath)) {
            $this->error("SQLite database not found: $dbPath");
            return Result::error($this);
        }

        if ($compress) {
            return $this->taskExec("gzip -c " . escapeshellarg($dbPath) . " > " . escapeshellarg($backupPath))->run();
        } else {
            copy($dbPath, $backupPath);
            return Result::success($this);
        }
    }

    /**
     * Restore database from backup
     */
    protected function restoreBackup(array $config, string $backupPath): Result
    {
        $connection = $config['connection'] ?? 'mysql';
        $isCompressed = str_ends_with($backupPath, '.gz');

        switch ($connection) {
            case 'mysql':
            case 'mariadb':
                return $this->restoreMySQL($config, $backupPath, $isCompressed);

            case 'pgsql':
                return $this->restorePostgreSQL($config, $backupPath, $isCompressed);

            case 'sqlite':
                return $this->restoreSQLite($config, $backupPath, $isCompressed);

            default:
                $this->error("Unsupported database connection: $connection");
                return Result::error($this);
        }
    }

    /**
     * Restore MySQL database
     */
    protected function restoreMySQL(array $config, string $backupPath, bool $isCompressed): Result
    {
        $decompressCmd = $isCompressed ? 'gunzip -c ' . escapeshellarg($backupPath) . ' | ' : 'cat ' . escapeshellarg($backupPath) . ' | ';

        $command = $decompressCmd . sprintf(
            'mysql -h%s -P%s -u%s %s %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            $config['password'] ? '-p' . escapeshellarg($config['password']) : '',
            escapeshellarg($config['database'])
        );

        return $this->taskExec($command)->run();
    }

    /**
     * Restore PostgreSQL database
     */
    protected function restorePostgreSQL(array $config, string $backupPath, bool $isCompressed): Result
    {
        $decompressCmd = $isCompressed ? 'gunzip -c ' . escapeshellarg($backupPath) . ' | ' : 'cat ' . escapeshellarg($backupPath) . ' | ';

        $command = $decompressCmd . sprintf(
            'PGPASSWORD=%s psql -h %s -p %s -U %s %s',
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database'])
        );

        return $this->taskExec($command)->run();
    }

    /**
     * Restore SQLite database
     */
    protected function restoreSQLite(array $config, string $backupPath, bool $isCompressed): Result
    {
        $dbPath = $config['database'];

        if ($isCompressed) {
            return $this->taskExec("gunzip -c " . escapeshellarg($backupPath) . " > " . escapeshellarg($dbPath))->run();
        } else {
            copy($backupPath, $dbPath);
            return Result::success($this);
        }
    }

    /**
     * Get database configuration from .env
     */
    protected function getDatabaseConfig(): ?array
    {
        if (!file_exists('.env')) {
            return null;
        }

        $env = file_get_contents('.env');
        $config = [];

        // Parse .env file
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
     * Rotate old backups
     */
    protected function rotateBackups(string $backupDir, int $keep = 7): void
    {
        $backups = glob("$backupDir/*.sql*");

        if (count($backups) <= $keep) {
            return;
        }

        // Sort by modification time (oldest first)
        usort($backups, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Delete old backups
        $toDelete = array_slice($backups, 0, -$keep);

        foreach ($toDelete as $backup) {
            unlink($backup);
        }
    }

    /**
     * Format bytes to human readable size
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
