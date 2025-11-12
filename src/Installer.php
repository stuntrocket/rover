<?php

namespace Rover;

use Composer\Script\Event;

/**
 * Composer installer script for Rover
 *
 * Automatically copies the rover CLI helper script to the project root
 */
class Installer
{
    /**
     * Post-install script
     *
     * @param Event $event
     */
    public static function postInstall(Event $event)
    {
        self::installRoverScript($event);
    }

    /**
     * Post-update script
     *
     * @param Event $event
     */
    public static function postUpdate(Event $event)
    {
        self::installRoverScript($event);
    }

    /**
     * Install the rover CLI helper script
     *
     * @param Event $event
     */
    private static function installRoverScript(Event $event)
    {
        $io = $event->getIO();

        // Determine if we're installed as a dependency
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $projectRoot = dirname($vendorDir);

        // Source and destination paths
        $source = $vendorDir . '/stuntrocket/rover/bin/rover';
        $destination = $projectRoot . '/rover';

        // Check if source exists
        if (!file_exists($source)) {
            $io->writeError('<warning>Rover: Could not find rover script at ' . $source . '</warning>');
            return;
        }

        // Check if destination already exists
        if (file_exists($destination)) {
            // Check if it's already the same file
            if (file_get_contents($source) === file_get_contents($destination)) {
                $io->write('<info>Rover: CLI helper script is already up to date</info>');
                return;
            }

            // Ask user if they want to overwrite
            if (!$io->isInteractive()) {
                $io->write('<info>Rover: Skipping rover script installation (non-interactive mode)</info>');
                return;
            }

            $overwrite = $io->askConfirmation(
                '<question>Rover: rover script already exists. Overwrite? (y/n)</question> ',
                false
            );

            if (!$overwrite) {
                $io->write('<info>Rover: Keeping existing rover script</info>');
                return;
            }
        }

        // Copy the file
        if (@copy($source, $destination)) {
            // Make it executable
            @chmod($destination, 0755);

            $io->write('<info>Rover: CLI helper script installed successfully!</info>');
            $io->write('<info>You can now use: ./rover rover:about</info>');
        } else {
            $io->writeError('<warning>Rover: Could not install rover script</warning>');
        }
    }
}
