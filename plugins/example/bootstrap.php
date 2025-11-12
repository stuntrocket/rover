<?php

/**
 * Example Plugin Bootstrap File
 *
 * This file is loaded when the plugin is activated.
 */

require_once __DIR__ . '/src/Plugin.php';
require_once __DIR__ . '/src/Commands/ExampleCommands.php';

// Initialize the plugin
new ExamplePlugin();
