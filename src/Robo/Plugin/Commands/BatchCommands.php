<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Batch operations across multiple Laravel projects
 */
class BatchCommands extends BaseCommand
{
    /**
     * Run a command across all Laravel projects
     *
     * @command rover:run-all
     * @param string $command The command to run in each project
     * @option $continue Continue even if command fails in a project
     */
    public function runAll(string $command, array $options = ['continue' => true]): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info("Running '$command' in " . count($projects) . " project(s)...");
        $this->say('');

        $successful = [];
        $failed = [];

        foreach ($projects as $project) {
            $this->say("→ $project");

            $originalDir = getcwd();
            chdir($project);

            // Run the command
            $result = $this->taskExec($command)->run();

            chdir($originalDir);

            if ($result->wasSuccessful()) {
                $this->success("  ✓ Success");
                $successful[] = $project;
            } else {
                $this->error("  ✗ Failed");
                $failed[] = $project;

                if (!$options['continue']) {
                    $this->error("Stopping due to failure in $project");
                    break;
                }
            }

            $this->say('');
        }

        // Summary
        $this->info('Summary:');
        $this->say("  Successful: " . count($successful) . " project(s)");
        $this->say("  Failed:     " . count($failed) . " project(s)");

        if (!empty($failed)) {
            $this->warning("Failed in: " . implode(', ', $failed));
        }

        return count($failed) === 0 ? new ResultData(0, "") : new ResultData(1, "");
    }

    /**
     * Update composer dependencies in all projects
     *
     * @command rover:update-all
     * @option $dev Update dev dependencies only
     * @option $continue Continue even if update fails
     */
    public function updateAll(array $options = ['dev' => false, 'continue' => true]): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->warning('This will update composer dependencies in all projects!');
        if (!$this->io()->confirm('Continue?', false)) {
            return Result::cancelled();
        }

        $this->info("Updating dependencies in " . count($projects) . " project(s)...");
        $this->say('');

        $successful = [];
        $failed = [];

        foreach ($projects as $project) {
            $this->say("→ $project");

            $originalDir = getcwd();
            chdir($project);

            // Build command
            $command = 'composer update';
            if ($options['dev']) {
                $command .= ' --dev';
            }

            $result = $this->taskExec($command)->run();

            chdir($originalDir);

            if ($result->wasSuccessful()) {
                $this->success("  ✓ Updated");
                $successful[] = $project;
            } else {
                $this->error("  ✗ Failed");
                $failed[] = $project;

                if (!$options['continue']) {
                    break;
                }
            }

            $this->say('');
        }

        // Summary
        $this->info('Update Summary:');
        $this->say("  Updated: " . count($successful) . " project(s)");
        $this->say("  Failed:  " . count($failed) . " project(s)");

        return count($failed) === 0 ? new ResultData(0, "") : new ResultData(1, "");
    }

    /**
     * Run tests in all projects
     *
     * @command rover:test-all
     * @option $continue Continue even if tests fail
     */
    public function testAll(array $options = ['continue' => true]): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info("Running tests in " . count($projects) . " project(s)...");
        $this->say('');

        $passed = [];
        $failed = [];
        $skipped = [];

        foreach ($projects as $project) {
            $this->say("→ $project");

            $originalDir = getcwd();
            chdir($project);

            // Check if test runner exists
            $hasPest = file_exists('vendor/bin/pest');
            $hasPhpUnit = file_exists('vendor/bin/phpunit');

            if (!$hasPest && !$hasPhpUnit) {
                $this->warning("  ⊘ No test runner found");
                $skipped[] = $project;
                chdir($originalDir);
                $this->say('');
                continue;
            }

            // Run tests
            $command = $hasPest ? 'vendor/bin/pest' : 'vendor/bin/phpunit';
            $result = $this->taskExec($command)->run();

            chdir($originalDir);

            if ($result->wasSuccessful()) {
                $this->success("  ✓ Tests passed");
                $passed[] = $project;
            } else {
                $this->error("  ✗ Tests failed");
                $failed[] = $project;

                if (!$options['continue']) {
                    $this->error("Stopping due to test failures in $project");
                    break;
                }
            }

            $this->say('');
        }

        // Summary
        $this->info('Test Summary:');
        $this->say("  Passed:  " . count($passed) . " project(s)");
        $this->say("  Failed:  " . count($failed) . " project(s)");
        $this->say("  Skipped: " . count($skipped) . " project(s)");

        if (!empty($failed)) {
            $this->warning("Tests failed in: " . implode(', ', $failed));
        }

        return count($failed) === 0 ? new ResultData(0, "") : new ResultData(1, "");
    }

    /**
     * Pull latest changes from git in all projects
     *
     * @command rover:git:pull-all
     * @option $continue Continue even if pull fails
     */
    public function gitPullAll(array $options = ['continue' => true]): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info("Pulling latest changes in " . count($projects) . " project(s)...");
        $this->say('');

        $successful = [];
        $failed = [];
        $skipped = [];

        foreach ($projects as $project) {
            $this->say("→ $project");

            if (!is_dir("$project/.git")) {
                $this->warning("  ⊘ Not a git repository");
                $skipped[] = $project;
                $this->say('');
                continue;
            }

            $originalDir = getcwd();
            chdir($project);

            // Check for uncommitted changes
            $status = shell_exec('git status --porcelain 2>/dev/null');
            if (!empty($status)) {
                $this->warning("  ⚠ Uncommitted changes - skipping");
                $skipped[] = $project;
                chdir($originalDir);
                $this->say('');
                continue;
            }

            // Pull
            $result = $this->taskExec('git pull')->run();

            chdir($originalDir);

            if ($result->wasSuccessful()) {
                $this->success("  ✓ Pulled");
                $successful[] = $project;
            } else {
                $this->error("  ✗ Failed");
                $failed[] = $project;

                if (!$options['continue']) {
                    break;
                }
            }

            $this->say('');
        }

        // Summary
        $this->info('Pull Summary:');
        $this->say("  Pulled:  " . count($successful) . " project(s)");
        $this->say("  Failed:  " . count($failed) . " project(s)");
        $this->say("  Skipped: " . count($skipped) . " project(s)");

        return count($failed) === 0 ? new ResultData(0, "") : new ResultData(1, "");
    }

    /**
     * Clear caches in all projects
     *
     * @command rover:clear-all
     */
    public function clearAll(): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info("Clearing caches in " . count($projects) . " project(s)...");
        $this->say('');

        foreach ($projects as $project) {
            $this->say("→ $project");

            $originalDir = getcwd();
            chdir($project);

            // Clear Laravel caches
            $this->taskExec('php artisan cache:clear')->run();
            $this->taskExec('php artisan config:clear')->run();
            $this->taskExec('php artisan route:clear')->run();
            $this->taskExec('php artisan view:clear')->run();

            chdir($originalDir);

            $this->success("  ✓ Cleared");
            $this->say('');
        }

        $this->success('All caches cleared!');

        return new ResultData(0, "");
    }

    /**
     * Run composer install in all projects
     *
     * @command rover:install-all
     * @option $continue Continue even if install fails
     */
    public function installAll(array $options = ['continue' => true]): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info("Installing dependencies in " . count($projects) . " project(s)...");
        $this->say('');

        $successful = [];
        $failed = [];

        foreach ($projects as $project) {
            $this->say("→ $project");

            $originalDir = getcwd();
            chdir($project);

            $result = $this->taskExec('composer install')->run();

            chdir($originalDir);

            if ($result->wasSuccessful()) {
                $this->success("  ✓ Installed");
                $successful[] = $project;
            } else {
                $this->error("  ✗ Failed");
                $failed[] = $project;

                if (!$options['continue']) {
                    break;
                }
            }

            $this->say('');
        }

        // Summary
        $this->info('Install Summary:');
        $this->say("  Installed: " . count($successful) . " project(s)");
        $this->say("  Failed:    " . count($failed) . " project(s)");

        return count($failed) === 0 ? new ResultData(0, "") : new ResultData(1, "");
    }

    /**
     * Find Laravel projects helper
     *
     * @param string $directory
     * @return array
     */
    protected function findLaravelProjects(string $directory): array
    {
        $projects = [];
        $d = dir($directory);

        if (!$d) {
            return $projects;
        }

        while (false !== ($entry = $d->read())) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = "$directory/$entry";
            if (is_dir($path) && file_exists("$path/artisan")) {
                $projects[] = $entry;
            }
        }

        $d->close();
        sort($projects);

        return $projects;
    }
}
