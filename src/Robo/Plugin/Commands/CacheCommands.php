<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Cache management commands for Laravel projects
 */
class CacheCommands extends BaseCommand
{
    /**
     * Clear all Laravel caches
     *
     * Clears config, route, view, cache, and compiled files.
     *
     * @command rover:clear
     * @aliases clear
     */
    public function clear(): Result
    {
        $this->requireLaravelProject();

        $this->info('Clearing all Laravel caches...');

        $caches = [
            'config' => 'Configuration cache',
            'route' => 'Route cache',
            'view' => 'View cache',
            'cache' => 'Application cache',
        ];

        $cleared = [];
        $failed = [];

        foreach ($caches as $cache => $description) {
            $this->say("Clearing $description...");
            $result = $this->artisan("$cache:clear");

            if ($result->wasSuccessful()) {
                $cleared[] = $description;
            } else {
                $failed[] = $description;
            }
        }

        // Clear compiled classes
        $this->say('Clearing compiled classes...');
        $compileResult = $this->artisan('clear-compiled');

        if ($compileResult->wasSuccessful()) {
            $cleared[] = 'Compiled classes';
        } else {
            $failed[] = 'Compiled classes';
        }

        // Display results
        if (!empty($cleared)) {
            $this->success('Cleared: ' . implode(', ', $cleared));
        }

        if (!empty($failed)) {
            $this->error('Failed to clear: ' . implode(', ', $failed));
            return Result::error($this);
        }

        $this->success('All caches cleared successfully!');
        return Result::success($this);
    }

    /**
     * Run all Laravel optimization commands
     *
     * Caches config, routes, and views for optimal performance.
     *
     * @command rover:optimize
     * @aliases optimize
     */
    public function optimize(): Result
    {
        $this->requireLaravelProject();

        $this->info('Optimizing Laravel application...');

        $optimizations = [
            'config:cache' => 'Configuration',
            'route:cache' => 'Routes',
            'view:cache' => 'Views',
        ];

        $optimized = [];
        $failed = [];

        foreach ($optimizations as $command => $description) {
            $this->say("Caching $description...");
            $result = $this->artisan($command);

            if ($result->wasSuccessful()) {
                $optimized[] = $description;
            } else {
                $failed[] = $description;
            }
        }

        // Run optimize command
        $this->say('Running optimize...');
        $optimizeResult = $this->artisan('optimize');

        if ($optimizeResult->wasSuccessful()) {
            $optimized[] = 'Application';
        } else {
            $failed[] = 'Application';
        }

        // Display results
        if (!empty($optimized)) {
            $this->success('Optimized: ' . implode(', ', $optimized));
        }

        if (!empty($failed)) {
            $this->error('Failed to optimize: ' . implode(', ', $failed));
            return Result::error($this);
        }

        $this->success('Application optimized successfully!');
        $this->info('Note: Remember to run "rover:clear" in development to disable caching.');

        return Result::success($this);
    }

    /**
     * Clear and optimize in one command
     *
     * Clears all caches then runs optimization commands.
     *
     * @command rover:refresh
     * @aliases refresh
     */
    public function refresh(): Result
    {
        $this->requireLaravelProject();

        $this->info('Refreshing Laravel application...');

        // Clear first
        $clearResult = $this->clear();

        if (!$clearResult->wasSuccessful()) {
            return $clearResult;
        }

        // Then optimize
        $optimizeResult = $this->optimize();

        if ($optimizeResult->wasSuccessful()) {
            $this->success('Application refreshed successfully!');
        }

        return $optimizeResult;
    }
}
