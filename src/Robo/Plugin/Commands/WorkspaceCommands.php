<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Workspace and multi-project management commands
 */
class WorkspaceCommands extends BaseCommand
{
    /**
     * Health check across all Laravel projects
     *
     * @command rover:health
     * @aliases health
     */
    public function health(): Result|ResultData
    {
        $this->info('Running health checks on all Laravel projects...');

        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->warning('No Laravel projects found');
            return new ResultData(0, "");
        }

        $this->say('');
        $issues = [];
        $healthy = [];

        foreach ($projects as $project) {
            $this->say("Checking $project...");

            $originalDir = getcwd();
            chdir($project);

            $projectIssues = [];

            // Check composer.lock exists
            if (!file_exists('composer.lock')) {
                $projectIssues[] = 'Missing composer.lock - run composer install';
            }

            // Check vendor directory
            if (!is_dir('vendor')) {
                $projectIssues[] = 'Missing vendor directory - run composer install';
            }

            // Check .env file
            if (!file_exists('.env')) {
                $projectIssues[] = 'Missing .env file';
            }

            // Check APP_KEY
            if (file_exists('.env')) {
                $env = file_get_contents('.env');
                if (preg_match('/^APP_KEY=(.*)$/m', $env, $matches)) {
                    $key = trim($matches[1]);
                    if (empty($key) || $key === '') {
                        $projectIssues[] = 'APP_KEY not generated';
                    }
                } else {
                    $projectIssues[] = 'APP_KEY not found in .env';
                }
            }

            // Check storage permissions
            if (is_dir('storage')) {
                if (!is_writable('storage')) {
                    $projectIssues[] = 'storage directory not writable';
                }
            }

            // Check if git is clean
            if (is_dir('.git')) {
                $status = shell_exec('git status --porcelain 2>/dev/null');
                if (!empty($status)) {
                    $changes = count(explode("\n", trim($status)));
                    $projectIssues[] = "$changes uncommitted change(s)";
                }
            }

            // Check for outdated dependencies
            $outdated = shell_exec('composer outdated --direct --format=json 2>/dev/null');
            if ($outdated) {
                $data = json_decode($outdated, true);
                if (isset($data['installed']) && !empty($data['installed'])) {
                    $projectIssues[] = count($data['installed']) . ' outdated package(s)';
                }
            }

            chdir($originalDir);

            if (empty($projectIssues)) {
                $this->success("  ✓ Healthy");
                $healthy[] = $project;
            } else {
                $this->warning("  ⚠ Issues found:");
                foreach ($projectIssues as $issue) {
                    $this->say("    - $issue");
                }
                $issues[$project] = $projectIssues;
            }

            $this->say('');
        }

        // Summary
        $this->info('Health Check Summary:');
        $this->say("  Healthy: " . count($healthy) . " project(s)");
        $this->say("  Issues:  " . count($issues) . " project(s)");

        return new ResultData(0, "");
    }

    /**
     * Switch to a different Laravel project
     *
     * @command rover:switch
     * @aliases switch
     * @param string|null $project Project name to switch to
     */
    public function switchProject(?string $project = null): Result|ResultData
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        // If no project specified, show interactive selection
        if (!$project) {
            $this->info('Available projects:');
            foreach ($projects as $index => $proj) {
                $this->say("  " . ($index + 1) . ". $proj");
            }

            $selection = $this->io()->ask('Select project number');
            $index = (int)$selection - 1;

            if (!isset($projects[$index])) {
                $this->error('Invalid selection');
                return new ResultData(1, "");
            }

            $project = $projects[$index];
        }

        // Validate project exists
        if (!in_array($project, $projects)) {
            $this->error("Project '$project' not found");
            $this->info('Available projects: ' . implode(', ', $projects));
            return new ResultData(1, "");
        }

        // Generate switch command
        $this->success("To switch to $project, run:");
        $this->say("  cd $project");

        // Show project info
        if (file_exists("$project/composer.json")) {
            $composer = json_decode(file_get_contents("$project/composer.json"), true);
            if (isset($composer['require']['laravel/framework'])) {
                $this->info("Laravel version: " . $composer['require']['laravel/framework']);
            }
        }

        return new ResultData(0, "");
    }

    /**
     * Overview of all projects (enhanced version)
     *
     * @command rover:workspace:status
     */
    public function workspaceStatus(): Result|ResultData
    {
        $this->info('Workspace Status Overview');
        $this->say('');

        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->warning('No Laravel projects found');
            return new ResultData(0, "");
        }

        foreach ($projects as $project) {
            $this->say("📦 $project");

            $originalDir = getcwd();
            chdir($project);

            // Laravel version
            if (file_exists('composer.json')) {
                $composer = json_decode(file_get_contents('composer.json'), true);
                if (isset($composer['require']['laravel/framework'])) {
                    $this->say("   Framework: " . $composer['require']['laravel/framework']);
                }
            }

            // Git status
            if (is_dir('.git')) {
                $branch = trim(shell_exec('git symbolic-ref --short HEAD 2>/dev/null'));
                if ($branch) {
                    $this->say("   Branch: $branch");
                }

                $status = shell_exec('git status --porcelain 2>/dev/null');
                if (empty($status)) {
                    $this->say("   Git: ✓ Clean");
                } else {
                    $changes = count(explode("\n", trim($status)));
                    $this->say("   Git: ⚠ $changes uncommitted change(s)");
                }
            }

            // Environment
            if (file_exists('.env')) {
                $env = file_get_contents('.env');
                if (preg_match('/^APP_ENV=(.*)$/m', $env, $matches)) {
                    $appEnv = trim($matches[1]);
                    $this->say("   Environment: $appEnv");
                }
            }

            // Dependencies
            if (file_exists('composer.lock')) {
                $this->say("   Composer: ✓ Locked");
            } else {
                $this->say("   Composer: ⚠ Not locked");
            }

            chdir($originalDir);
            $this->say('');
        }

        return new ResultData(0, "");
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

    /**
     * Compare Laravel versions across projects
     *
     * @command rover:workspace:versions
     */
    public function compareVersions(): Result|ResultData
    {
        $this->info('Laravel versions across projects:');
        $this->say('');

        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->warning('No Laravel projects found');
            return new ResultData(0, "");
        }

        $versions = [];

        foreach ($projects as $project) {
            if (file_exists("$project/composer.json")) {
                $composer = json_decode(file_get_contents("$project/composer.json"), true);
                if (isset($composer['require']['laravel/framework'])) {
                    $version = $composer['require']['laravel/framework'];
                    $versions[$version][] = $project;
                }
            }
        }

        foreach ($versions as $version => $projects) {
            $this->say("Laravel $version (" . count($projects) . " project(s)):");
            foreach ($projects as $project) {
                $this->say("  - $project");
            }
            $this->say('');
        }

        return new ResultData(0, "");
    }
}
