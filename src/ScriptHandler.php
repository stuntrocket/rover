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
            // Check the top-level vendor directory first
            $roboSrcTopLevel = __DIR__ . '/../../../vendor/consolidation/robo/robo';
            // Check the nested vendor directory second
            $roboSrcNested = InstalledVersions::getInstallPath('consolidation/robo') . '/robo';

            // Determine the actual source path based on the location of the robo binary
            $roboSrc = file_exists($roboSrcTopLevel) ? $roboSrcTopLevel : $roboSrcNested;

            // Destination path remains the same
            $roboDestDir = __DIR__ . '/../../../vendor/bin';
            $roboDest = $roboDestDir . '/robo';

            if (!file_exists($roboDestDir)) {
                mkdir($roboDestDir, 0755, true);
            }

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
