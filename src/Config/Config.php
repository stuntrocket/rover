<?php

namespace Rover\Config;

/**
 * Configuration management for Rover
 *
 * Handles loading and accessing configuration from rover.yml files
 */
class Config
{
    protected array $config = [];
    protected static ?Config $instance = null;

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Load configuration from file
     */
    public function load(string $path = 'rover.yml'): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        // Simple YAML parser (without external dependencies)
        $content = file_get_contents($path);
        $this->config = $this->parseSimpleYaml($content);

        return true;
    }

    /**
     * Get configuration value
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set configuration value
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Simple YAML parser (supports basic key-value pairs and nested structures)
     * For production use, consider using symfony/yaml
     */
    protected function parseSimpleYaml(string $content): array
    {
        $result = [];
        $lines = explode("\n", $content);
        $currentArray = &$result;
        $stack = [];

        foreach ($lines as $line) {
            // Skip comments and empty lines
            if (trim($line) === '' || str_starts_with(trim($line), '#')) {
                continue;
            }

            // Get indentation level
            preg_match('/^(\s*)/', $line, $matches);
            $indent = strlen($matches[1]);
            $line = trim($line);

            // Parse key-value pair
            if (str_contains($line, ':')) {
                [$key, $value] = array_map('trim', explode(':', $line, 2));

                // Handle arrays
                if ($value === '') {
                    $currentArray[$key] = [];
                    $stack[$indent] = &$currentArray[$key];
                } else {
                    // Parse value
                    $parsedValue = $this->parseValue($value);

                    if ($indent === 0) {
                        $result[$key] = $parsedValue;
                        $currentArray = &$result;
                    } else {
                        $stack[$indent - 2][$key] = $parsedValue;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Parse YAML value
     */
    protected function parseValue(string $value)
    {
        // Boolean values
        if (in_array(strtolower($value), ['true', 'yes', 'on'])) {
            return true;
        }
        if (in_array(strtolower($value), ['false', 'no', 'off'])) {
            return false;
        }

        // Null
        if (strtolower($value) === 'null' || $value === '~') {
            return null;
        }

        // Numbers
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        // Strings
        // Remove quotes if present
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Create default configuration file
     */
    public static function createDefault(string $path = 'rover.yml'): bool
    {
        $defaultConfig = <<<'YAML'
# Rover Configuration File
# This file contains team-wide settings for Rover commands

# Team information
team:
  name: StuntRocket
  email: hello@stuntrocket.co

# Code quality settings
quality:
  # Laravel Pint configuration
  pint:
    preset: laravel

  # Testing configuration
  testing:
    parallel: false
    coverage: false

# Database settings
database:
  # Always ask for confirmation in these environments
  require_confirmation:
    - production
    - staging

  # Backup settings
  backup:
    path: ./storage/backups
    keep: 7 # Keep last 7 backups

# Project settings
projects:
  # Workspace directory where projects are located
  workspace: ~/Sites

  # Directories to exclude from project scanning
  exclude:
    - node_modules
    - vendor
    - storage

# Development settings
development:
  # Default packages to install in new projects
  packages:
    require-dev:
      - laravel/pint
      - barryvdh/laravel-ide-helper
      - spatie/laravel-ray
      - pestphp/pest
      - pestphp/pest-plugin-laravel

  # Git hooks to install
  git_hooks:
    pre-commit:
      - rover:check

# Deployment settings (future use)
deployment:
  strategy: zero-downtime
  backup_before_deploy: true

YAML;

        return file_put_contents($path, $defaultConfig) !== false;
    }
}
