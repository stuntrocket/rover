<?php

// src/ScriptHandler.php
namespace Rover;

use Composer\InstalledVersions;
use Exception;

class ScriptHandler
{
    public static function createSymlink(): void
    {
        try {
            $roboSrc = InstalledVersions::getInstallPath('consolidation/robo') . '/robo';
            $roboDest = __DIR__ . '/../vendor/bin/robo';

            if (file_exists($roboDest)) {
                throw new Exception("A file or symlink already exists at {$roboDest}. Aborting to prevent overwriting.");
            }

            if (!file_exists($roboSrc)) {
                throw new Exception("Source file not found at {$roboSrc}.");
            }

            symlink($roboSrc, $roboDest);
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }
    }
}