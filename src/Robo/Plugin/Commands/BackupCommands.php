<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Symfony\ConsoleIO;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see https://robo.li/
 */
class BackupCommands extends \Robo\Tasks
{

    /**
     * @command rover:sitelist
     * List site directories
     *
     * @return array
     */
    public function siteList()
    {
        $f = [];
        $d = dir('.');

        if ($d) {
            while (false !== ($entry = $d->read())) {
                if (is_dir($entry) && !in_array($entry, ['.', '..'])) {
                    $f[] = $entry;
                }
            }
            $d->close();
        }

        return $f;
    }
}
