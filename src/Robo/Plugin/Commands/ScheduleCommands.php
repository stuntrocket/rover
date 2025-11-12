<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Schedule management and testing commands
 */
class ScheduleCommands extends BaseCommand
{
    /**
     * List all scheduled commands
     *
     * @command rover:schedule:list
     * @aliases schedule:list
     */
    public function listSchedule(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Scheduled Commands:');
        $this->say('');

        return $this->artisan('schedule:list');
    }

    /**
     * Run scheduled commands for testing
     *
     * @command rover:schedule:run
     */
    public function runSchedule(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Running scheduled commands...');
        $this->say('');

        return $this->artisan('schedule:run');
    }

    /**
     * Test scheduled commands (run immediately)
     *
     * @command rover:schedule:test
     * @param string|null $command Specific command to test
     */
    public function testSchedule(?string $command = null): Result|ResultData
    {
        $this->requireLaravelProject();

        if ($command) {
            $this->info("Testing command: $command");
            return $this->artisan($command);
        }

        $this->info('Testing all scheduled commands...');
        $this->say('');
        $this->warning('This will run ALL scheduled commands immediately!');

        if (!$this->io()->confirm('Continue?', false)) {
            return Result::cancelled();
        }

        return $this->artisan('schedule:run');
    }

    /**
     * Show schedule work status
     *
     * @command rover:schedule:work
     */
    public function scheduleWork(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Running scheduler in foreground (for development)...');
        $this->info('Press Ctrl+C to stop');
        $this->say('');

        return $this->artisan('schedule:work');
    }

    /**
     * Verify cron is set up correctly
     *
     * @command rover:schedule:check
     */
    public function checkSchedule(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Checking schedule configuration...');
        $this->say('');

        // Check if schedule:list works
        $listResult = $this->taskExec('php artisan schedule:list')
            ->printOutput(false)
            ->run();

        if (!$listResult->wasSuccessful()) {
            $this->error('✗ Could not list scheduled commands');
            return new ResultData(1, "");
        }

        $this->success('✓ Schedule commands are configured');

        // Check if cron is set up
        $this->say('');
        $this->say('Cron Setup:');

        $crontab = shell_exec('crontab -l 2>/dev/null | grep schedule:run');

        if (empty($crontab)) {
            $this->warning('⚠ Cron job not found!');
            $this->say('');
            $this->info('Add this to your crontab (crontab -e):');
            $this->say('  * * * * * cd ' . getcwd() . ' && php artisan schedule:run >> /dev/null 2>&1');
        } else {
            $this->success('✓ Cron job is configured');
            $this->say('  ' . trim($crontab));
        }

        // Show recent schedule runs
        $this->say('');
        $this->say('Recent schedule runs:');

        $logFile = 'storage/logs/laravel.log';
        if (file_exists($logFile)) {
            $recentRuns = shell_exec("grep 'Running scheduled command' $logFile | tail -5");

            if ($recentRuns) {
                echo $recentRuns;
            } else {
                $this->say('  No recent runs found in logs');
            }
        }

        return new ResultData(0, "");
    }

    /**
     * Generate schedule documentation
     *
     * @command rover:schedule:docs
     */
    public function generateDocs(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Generating schedule documentation...');

        // Get schedule list
        $output = shell_exec('php artisan schedule:list');

        if (empty($output)) {
            $this->warning('No scheduled commands found.');
            return new ResultData(0, "");
        }

        // Create docs directory
        if (!is_dir('docs')) {
            mkdir('docs', 0755, true);
        }

        $docsFile = 'docs/SCHEDULE.md';

        $content = "# Scheduled Commands\n\n";
        $content .= "This document lists all scheduled commands for this Laravel application.\n\n";
        $content .= "## Commands\n\n";
        $content .= "```\n";
        $content .= $output;
        $content .= "```\n\n";
        $content .= "## Setup\n\n";
        $content .= "Add this to your crontab:\n\n";
        $content .= "```bash\n";
        $content .= "* * * * * cd " . getcwd() . " && php artisan schedule:run >> /dev/null 2>&1\n";
        $content .= "```\n\n";
        $content .= "## Testing\n\n";
        $content .= "Test scheduled commands with:\n\n";
        $content .= "```bash\n";
        $content .= "vendor/bin/robo rover:schedule:test\n";
        $content .= "```\n";

        file_put_contents($docsFile, $content);

        $this->success("✓ Documentation generated: $docsFile");

        return new ResultData(0, "");
    }
}
