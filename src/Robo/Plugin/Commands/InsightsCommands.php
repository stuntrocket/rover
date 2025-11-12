<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Project insights and analytics commands
 */
class InsightsCommands extends BaseCommand
{
    /**
     * Generate project statistics
     *
     * @command rover:insights:stats
     * @param string|null $project Project name (defaults to current directory)
     */
    public function stats(?string $project = null): Result
    {
        $targetDir = $project ?? '.';

        if (!file_exists("$targetDir/artisan")) {
            $this->error('Not a Laravel project');
            return new ResultData(1, "");
        }

        $originalDir = getcwd();
        if ($project) {
            chdir($project);
        }

        $this->info('Generating project statistics...');
        $this->say('');

        // Project name
        $projectName = basename(getcwd());
        $this->say("📊 Project: $projectName");
        $this->say('');

        // Lines of code
        $this->say('Code Statistics:');
        $appLoc = $this->countLines('app');
        $testLoc = $this->countLines('tests');
        $totalLoc = $appLoc + $testLoc;

        $this->say("  Application code: $appLoc lines");
        $this->say("  Test code:        $testLoc lines");
        $this->say("  Total:            $totalLoc lines");

        if ($appLoc > 0 && $testLoc > 0) {
            $ratio = round($testLoc / $appLoc * 100, 1);
            $this->say("  Test ratio:       $ratio%");
        }

        $this->say('');

        // File counts
        $this->say('File Counts:');
        $controllers = count(glob('app/Http/Controllers/*.php'));
        $models = count(glob('app/Models/*.php'));
        $migrations = count(glob('database/migrations/*.php'));
        $tests = count(glob('tests/**/*Test.php', GLOB_BRACE));

        $this->say("  Controllers:  $controllers");
        $this->say("  Models:       $models");
        $this->say("  Migrations:   $migrations");
        $this->say("  Tests:        $tests");

        $this->say('');

        // Dependencies
        if (file_exists('composer.json')) {
            $composer = json_decode(file_get_contents('composer.json'), true);

            $this->say('Dependencies:');
            $require = isset($composer['require']) ? count($composer['require']) : 0;
            $requireDev = isset($composer['require-dev']) ? count($composer['require-dev']) : 0;

            $this->say("  Production:   $require package(s)");
            $this->say("  Development:  $requireDev package(s)");

            // Laravel version
            if (isset($composer['require']['laravel/framework'])) {
                $this->say("  Laravel:      " . $composer['require']['laravel/framework']);
            }

            $this->say('');
        }

        // Git stats
        if (is_dir('.git')) {
            $this->say('Git Statistics:');

            $commits = trim(shell_exec('git rev-list --count HEAD 2>/dev/null') ?? '0');
            $branches = count(explode("\n", trim(shell_exec('git branch -a 2>/dev/null') ?? '')));
            $contributors = trim(shell_exec('git shortlog -s -n | wc -l 2>/dev/null') ?? '0');

            $this->say("  Total commits:    $commits");
            $this->say("  Branches:         $branches");
            $this->say("  Contributors:     $contributors");

            $this->say('');
        }

        // Environment
        if (file_exists('.env')) {
            $env = file_get_contents('.env');
            if (preg_match('/^APP_ENV=(.*)$/m', $env, $matches)) {
                $appEnv = trim($matches[1]);
                $this->say("Environment: $appEnv");
            }
        }

        if ($project) {
            chdir($originalDir);
        }

        return new ResultData(0, "");
    }

    /**
     * Compare dependencies across all projects
     *
     * @command rover:insights:dependencies
     */
    public function dependencies(): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info('Analyzing dependencies across projects...');
        $this->say('');

        $allPackages = [];

        foreach ($projects as $project) {
            if (file_exists("$project/composer.json")) {
                $composer = json_decode(file_get_contents("$project/composer.json"), true);

                if (isset($composer['require'])) {
                    foreach ($composer['require'] as $package => $version) {
                        if (!isset($allPackages[$package])) {
                            $allPackages[$package] = [];
                        }
                        $allPackages[$package][$version][] = $project;
                    }
                }
            }
        }

        // Find version inconsistencies
        $inconsistent = array_filter($allPackages, function($versions) {
            return count($versions) > 1;
        });

        if (empty($inconsistent)) {
            $this->success('All shared packages use consistent versions!');
        } else {
            $this->warning('Version inconsistencies found:');
            $this->say('');

            foreach ($inconsistent as $package => $versions) {
                $this->say("📦 $package");
                foreach ($versions as $version => $projects) {
                    $this->say("  $version: " . implode(', ', $projects));
                }
                $this->say('');
            }

            $this->info('Consider standardizing package versions across projects.');
        }

        return new ResultData(0, "");
    }

    /**
     * Security audit across all projects
     *
     * @command rover:insights:security
     */
    public function security(): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info('Running security audit on ' . count($projects) . ' project(s)...');
        $this->say('');

        $vulnerabilities = [];

        foreach ($projects as $project) {
            $this->say("→ $project");

            $originalDir = getcwd();
            chdir($project);

            // Run composer audit
            $result = shell_exec('composer audit --format=json 2>/dev/null');

            if ($result) {
                $audit = json_decode($result, true);

                if (isset($audit['advisories']) && !empty($audit['advisories'])) {
                    $count = count($audit['advisories']);
                    $this->error("  ⚠ $count vulnerabilit" . ($count === 1 ? 'y' : 'ies') . " found");
                    $vulnerabilities[$project] = $audit['advisories'];
                } else {
                    $this->success("  ✓ No known vulnerabilities");
                }
            } else {
                $this->warning("  ⊘ Could not run audit");
            }

            chdir($originalDir);
        }

        $this->say('');

        // Summary
        if (empty($vulnerabilities)) {
            $this->success('No security vulnerabilities found!');
        } else {
            $this->warning('Security vulnerabilities detected in ' . count($vulnerabilities) . ' project(s)');
            $this->info("\nRun 'composer audit' in affected projects for details.");
            $this->info("Run 'composer update' to fix vulnerabilities.");
        }

        return new ResultData(0, "");
    }

    /**
     * Show outdated packages across all projects
     *
     * @command rover:insights:outdated
     */
    public function outdated(): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info('Checking for outdated packages...');
        $this->say('');

        $hasOutdated = false;

        foreach ($projects as $project) {
            $this->say("→ $project");

            $originalDir = getcwd();
            chdir($project);

            $result = shell_exec('composer outdated --direct --format=json 2>/dev/null');

            if ($result) {
                $data = json_decode($result, true);

                if (isset($data['installed']) && !empty($data['installed'])) {
                    $count = count($data['installed']);
                    $this->warning("  ⚠ $count outdated package(s)");

                    foreach (array_slice($data['installed'], 0, 3) as $package) {
                        $this->say("    - {$package['name']}: {$package['version']} → {$package['latest']}");
                    }

                    if ($count > 3) {
                        $this->say("    ... and " . ($count - 3) . " more");
                    }

                    $hasOutdated = true;
                } else {
                    $this->success("  ✓ All packages up to date");
                }
            } else {
                $this->warning("  ⊘ Could not check");
            }

            chdir($originalDir);
            $this->say('');
        }

        if ($hasOutdated) {
            $this->info("Run 'rover:update-all' to update all projects");
        }

        return new ResultData(0, "");
    }

    /**
     * Generate a comprehensive workspace report
     *
     * @command rover:insights:report
     */
    public function report(): Result
    {
        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->error('No Laravel projects found');
            return new ResultData(1, "");
        }

        $this->info('Generating Workspace Report');
        $this->say('');
        $this->say('═══════════════════════════════════════════════════════');
        $this->say('');

        // Overview
        $this->say('📊 OVERVIEW');
        $this->say("   Total projects: " . count($projects));

        // Count Laravel versions
        $versions = [];
        foreach ($projects as $project) {
            if (file_exists("$project/composer.json")) {
                $composer = json_decode(file_get_contents("$project/composer.json"), true);
                if (isset($composer['require']['laravel/framework'])) {
                    $version = $composer['require']['laravel/framework'];
                    $versions[$version] = ($versions[$version] ?? 0) + 1;
                }
            }
        }

        foreach ($versions as $version => $count) {
            $this->say("   Laravel $version: $count project(s)");
        }

        $this->say('');

        // Git status summary
        $this->say('🔀 GIT STATUS');
        $clean = 0;
        $dirty = 0;

        foreach ($projects as $project) {
            if (is_dir("$project/.git")) {
                $status = shell_exec("cd $project && git status --porcelain 2>/dev/null");
                if (empty($status)) {
                    $clean++;
                } else {
                    $dirty++;
                }
            }
        }

        $this->say("   Clean repositories:  $clean");
        $this->say("   Dirty repositories:  $dirty");
        $this->say('');

        // Test coverage summary
        $this->say('✅ TESTING');
        $withTests = 0;
        foreach ($projects as $project) {
            if (is_dir("$project/tests") && count(glob("$project/tests/**/*Test.php", GLOB_BRACE)) > 0) {
                $withTests++;
            }
        }

        $this->say("   Projects with tests: $withTests/" . count($projects));
        $this->say('');

        $this->say('═══════════════════════════════════════════════════════');

        return new ResultData(0, "");
    }

    /**
     * Count lines of code in directory
     *
     * @param string $directory
     * @return int
     */
    protected function countLines(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $lines = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $lines += count(file($file->getRealPath()));
            }
        }

        return $lines;
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
