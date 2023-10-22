<?php

// src/ScriptHandler.php
namespace Rover;

use Exception;

class ScriptHandler
{
    public static function createSymlink()
    {
        try {
            $roboSrc = __DIR__ . '/../vendor/consolidation/robo/bin/robo';
            $roboDest = __DIR__ . '/../vendor/bin/robo';

            if (file_exists($roboDest)) {
                echo "A file or symlink already exists at {$roboDest}. Aborting to prevent overwriting.\n";
            } elseif (file_exists($roboSrc)) {
                symlink($roboSrc, $roboDest);
                echo "Symlink created successfully.\n";
            } else {
                echo "Source file not found at {$roboSrc}.\n";
            }
        } catch (\Exception $e) {
            echo 'Exception: ',  $e->getMessage(), "\n";
        }
    }
}
