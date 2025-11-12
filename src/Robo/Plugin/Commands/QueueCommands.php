<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Queue management and monitoring commands
 */
class QueueCommands extends BaseCommand
{
    /**
     * Monitor queue status
     *
     * @command rover:queue:monitor
     * @aliases queue:monitor
     * @option $queue Queue name to monitor
     */
    public function monitor(array $options = ['queue' => 'default']): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info("Monitoring queue: {$options['queue']}");
        $this->say('');

        // Check if queue table exists
        $result = $this->artisan('queue:failed');

        if (!$result->wasSuccessful()) {
            $this->warning('Queue tables may not be set up.');
            $this->info('Run: php artisan queue:table');
            $this->info('Then: php artisan migrate');
            return new ResultData(1, "");
        }

        // Show queue stats
        $this->say('Queue Statistics:');
        $this->say('');

        // Count failed jobs
        $failedCount = $this->getFailedJobCount();
        $this->say("Failed jobs: $failedCount");

        // Show queue workers
        $this->say('');
        $this->say('Queue Workers:');
        $workers = shell_exec('ps aux | grep "queue:work" | grep -v grep');

        if (empty($workers)) {
            $this->warning('  No queue workers running');
            $this->info('  Start with: php artisan queue:work');
        } else {
            $lines = explode("\n", trim($workers));
            $this->say('  ' . count($lines) . ' worker(s) running');
        }

        return new ResultData(0, "");
    }

    /**
     * Clear failed jobs
     *
     * @command rover:queue:clear
     * @option $force Skip confirmation
     */
    public function clear(array $options = ['force' => false]): Result|ResultData
    {
        $this->requireLaravelProject();

        $failedCount = $this->getFailedJobCount();

        if ($failedCount === 0) {
            $this->info('No failed jobs to clear.');
            return new ResultData(0, "");
        }

        $this->warning("Found $failedCount failed job(s)");

        if (!$options['force']) {
            if (!$this->io()->confirm('Clear all failed jobs?', false)) {
                return Result::cancelled();
            }
        }

        $result = $this->artisan('queue:flush');

        if ($result->wasSuccessful()) {
            $this->success('✓ Failed jobs cleared!');
        }

        return $result;
    }

    /**
     * Retry failed jobs
     *
     * @command rover:queue:retry-all
     * @option $queue Queue name
     */
    public function retryAll(array $options = ['queue' => null]): Result|ResultData
    {
        $this->requireLaravelProject();

        $failedCount = $this->getFailedJobCount();

        if ($failedCount === 0) {
            $this->info('No failed jobs to retry.');
            return new ResultData(0, "");
        }

        $this->info("Retrying $failedCount failed job(s)...");

        $result = $this->artisan('queue:retry', ['id' => 'all']);

        if ($result->wasSuccessful()) {
            $this->success('✓ Failed jobs queued for retry!');
        }

        return $result;
    }

    /**
     * Show failed jobs
     *
     * @command rover:queue:failed
     */
    public function failed(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Failed Jobs:');
        $this->say('');

        return $this->artisan('queue:failed');
    }

    /**
     * Restart queue workers
     *
     * @command rover:queue:restart
     */
    public function restart(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Restarting queue workers...');

        $result = $this->artisan('queue:restart');

        if ($result->wasSuccessful()) {
            $this->success('✓ Queue workers will restart gracefully');
            $this->info('Workers will finish current jobs then restart');
        }

        return $result;
    }

    /**
     * Run queue worker in development mode
     *
     * @command rover:queue:work
     * @option $queue Queue name
     * @option $tries Number of times to attempt a job
     */
    public function work(array $options = ['queue' => 'default', 'tries' => 3]): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info("Starting queue worker for: {$options['queue']}");
        $this->info('Press Ctrl+C to stop');
        $this->say('');

        return $this->artisan('queue:work', [
            'queue' => $options['queue'],
            'tries' => $options['tries'],
            'verbose' => true,
        ]);
    }

    /**
     * Get count of failed jobs
     */
    protected function getFailedJobCount(): int
    {
        // Try to count from database
        $output = shell_exec('php artisan queue:failed 2>/dev/null');

        if (empty($output)) {
            return 0;
        }

        // Count non-header lines
        $lines = explode("\n", trim($output));
        $count = 0;

        foreach ($lines as $line) {
            // Skip header and separator lines
            if (strpos($line, '|') !== false && strpos($line, 'ID') === false && strpos($line, '---') === false) {
                $count++;
            }
        }

        return $count;
    }
}
