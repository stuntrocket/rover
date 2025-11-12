<?php

use Rover\Plugin\BasePlugin;

/**
 * Example Plugin - Demonstrates plugin capabilities
 */
class ExamplePlugin extends BasePlugin
{
    public function boot(): void
    {
        $this->name = 'example';
        $this->version = '1.0.0';
        $this->description = 'Example plugin demonstrating Rover extensibility';

        // Register commands
        $this->registerCommand(ExampleCommands::class);

        // Register hooks
        $this->registerHook('before_command', function($data) {
            $this->log('Command about to execute', 'info');
        });

        $this->registerHook('after_command', function($data) {
            $this->log('Command execution completed', 'info');
        });

        $this->registerHook('project_init', function($data) {
            $this->log('New project initialized', 'info');
        });

        $this->registerHook('test_completed', function($data) {
            $result = $data['result'] ?? 'unknown';
            $this->log("Tests completed with result: $result", 'info');
        });

        // Example of using plugin configuration
        $greeting = $this->getConfig('greeting', 'Hello');
        $this->log("Plugin loaded with greeting: $greeting");
    }
}
