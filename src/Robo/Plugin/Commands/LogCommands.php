<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Log viewing and management commands
 */
class LogCommands extends BaseCommand
{
    /**
     * Tail Laravel logs with filtering
     *
     * @command rover:logs
     * @aliases logs
     * @option $lines Number of lines to show (default: 50)
     * @option $follow Follow log file (like tail -f)
     * @option $level Filter by level (emergency, alert, critical, error, warning, notice, info, debug)
     * @option $grep Filter by pattern
     */
    public function logs(array $options = [
        'lines' => 50,
        'follow' => false,
        'level' => null,
        'grep' => null
    ]): Result|ResultData
    {
        $this->requireLaravelProject();

        $logFile = 'storage/logs/laravel.log';

        if (!file_exists($logFile)) {
            $this->error('Log file not found: ' . $logFile);
            $this->info('No logs yet - application may not have been used.');
            return new ResultData(1, "");
        }

        $this->info('Laravel Logs:');
        $this->say('');

        // Build tail command
        $command = 'tail';

        if ($options['follow']) {
            $command .= ' -f';
        } else {
            $command .= ' -n ' . (int)$options['lines'];
        }

        // Add grep filter
        if ($options['level']) {
            $command .= " | grep -i '\\.{$options['level']}:'";
        }

        if ($options['grep']) {
            $command .= " | grep -i " . escapeshellarg($options['grep']);
        }

        $command .= " $logFile";

        // Execute tail command
        return $this->taskExec($command)->run();
    }

    /**
     * Clear Laravel logs
     *
     * @command rover:logs:clear
     * @option $force Skip confirmation
     */
    public function clearLogs(array $options = ['force' => false]): Result|ResultData
    {
        $this->requireLaravelProject();

        $logFile = 'storage/logs/laravel.log';

        if (!file_exists($logFile)) {
            $this->info('No log file to clear.');
            return new ResultData(0, "");
        }

        $size = $this->formatBytes(filesize($logFile));
        $this->info("Current log file size: $size");

        if (!$options['force']) {
            if (!$this->io()->confirm('Clear log file?', false)) {
                return Result::cancelled();
            }
        }

        // Truncate log file
        file_put_contents($logFile, '');

        $this->success('✓ Log file cleared!');

        return new ResultData(0, "");
    }

    /**
     * Show log statistics
     *
     * @command rover:logs:stats
     */
    public function logStats(): Result|ResultData
    {
        $this->requireLaravelProject();

        $logFile = 'storage/logs/laravel.log';

        if (!file_exists($logFile)) {
            $this->warning('No log file found.');
            return new ResultData(0, "");
        }

        $this->info('Log Statistics:');
        $this->say('');

        // File size
        $size = $this->formatBytes(filesize($logFile));
        $this->say("File size: $size");

        // Count lines
        $lines = 0;
        $handle = fopen($logFile, 'r');
        if ($handle) {
            while (!feof($handle)) {
                fgets($handle);
                $lines++;
            }
            fclose($handle);
        }
        $this->say("Total lines: " . number_format($lines));

        // Count by level
        $this->say('');
        $this->say('Errors by level:');

        $levels = [
            'EMERGENCY' => 0,
            'ALERT' => 0,
            'CRITICAL' => 0,
            'ERROR' => 0,
            'WARNING' => 0,
            'NOTICE' => 0,
            'INFO' => 0,
            'DEBUG' => 0,
        ];

        $handle = fopen($logFile, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                foreach ($levels as $level => $count) {
                    if (stripos($line, ".$level:") !== false) {
                        $levels[$level]++;
                        break;
                    }
                }
            }
            fclose($handle);
        }

        foreach ($levels as $level => $count) {
            if ($count > 0) {
                $this->say("  $level: " . number_format($count));
            }
        }

        return new ResultData(0, "");
    }

    /**
     * Find errors in logs
     *
     * @command rover:logs:errors
     * @option $lines Number of recent errors to show (default: 10)
     */
    public function findErrors(array $options = ['lines' => 10]): Result|ResultData
    {
        $this->requireLaravelProject();

        $logFile = 'storage/logs/laravel.log';

        if (!file_exists($logFile)) {
            $this->warning('No log file found.');
            return new ResultData(0, "");
        }

        $this->info('Recent Errors:');
        $this->say('');

        // Get errors
        $command = "grep -i '\\.ERROR:\\|\\.CRITICAL:\\|\\.EMERGENCY:' $logFile | tail -n " . (int)$options['lines'];

        $errors = shell_exec($command);

        if (empty($errors)) {
            $this->success('✓ No errors found in logs!');
        } else {
            echo $errors;
        }

        return new ResultData(0, "");
    }

    /**
     * Archive old logs
     *
     * @command rover:logs:archive
     */
    public function archiveLogs(): Result|ResultData
    {
        $this->requireLaravelProject();

        $logFile = 'storage/logs/laravel.log';

        if (!file_exists($logFile)) {
            $this->info('No log file to archive.');
            return new ResultData(0, "");
        }

        $archiveDir = 'storage/logs/archive';
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        $timestamp = date('Y-m-d_His');
        $archiveFile = "$archiveDir/laravel-$timestamp.log";

        // Copy and compress
        copy($logFile, $archiveFile);
        exec("gzip $archiveFile");

        // Clear original log
        file_put_contents($logFile, '');

        $this->success("✓ Logs archived to: $archiveFile.gz");
        $this->info('Original log file cleared.');

        return new ResultData(0, "");
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
