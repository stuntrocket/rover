<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Backup and project listing commands
 */
class BackupCommands extends BaseCommand
{
    /**
     * List Laravel projects in current directory
     *
     * @command rover:list
     * @aliases list
     */
    public function listProjects(): Result|ResultData
    {
        $this->info('Scanning for Laravel projects...');

        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->warning('No Laravel projects found in current directory.');
            return new ResultData(0, "");
        }

        $this->say("\nFound " . count($projects) . " Laravel project(s):\n");

        foreach ($projects as $project) {
            $this->say("  📁 $project");

            // Try to get Laravel version
            $composerPath = "$project/composer.json";
            if (file_exists($composerPath)) {
                $composer = json_decode(file_get_contents($composerPath), true);
                if (isset($composer['require']['laravel/framework'])) {
                    $version = $composer['require']['laravel/framework'];
                    $this->say("     Laravel: $version");
                }
            }
        }

        $this->say('');
        return new ResultData(0, "");
    }

    /**
     * Find Laravel projects in directory
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

            if (is_dir($path)) {
                // Check if it's a Laravel project
                if (file_exists("$path/artisan") && file_exists("$path/composer.json")) {
                    $projects[] = $entry;
                }
            }
        }

        $d->close();
        sort($projects);

        return $projects;
    }

    /**
     * Legacy command for backward compatibility
     *
     * @command rover:sitelist
     * @hidden
     */
    public function siteList(): array
    {
        return $this->findLaravelProjects('.');
    }
}
