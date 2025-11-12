# Example Plugin

This is an example plugin demonstrating Rover's extensibility system.

## Purpose

This plugin serves as a reference implementation showing how to:

- Create custom Rover commands
- Register and respond to hooks
- Access plugin configuration
- Integrate with Laravel projects
- Use BaseCommand utilities

## Commands

### `rover example:hello [name]`

Greet someone with a customizable message.

```bash
# Default greeting
rover example:hello

# Custom name
rover example:hello John
```

### `rover example:info`

Display information about the example plugin and its capabilities.

```bash
rover example:info
```

### `rover example:demo`

Demonstrate Laravel integration features including:
- Laravel project detection
- Version detection
- Package checking
- Artisan command execution

```bash
rover example:demo
```

## Configuration

Add to your `rover.yml`:

```yaml
plugins:
  example:
    enabled: true
    greeting: "Hello"  # Customize the greeting message
```

## Hooks

This plugin registers the following hooks:

- `before_command` - Logs before any command executes
- `after_command` - Logs after any command completes
- `project_init` - Logs when a new project is initialized
- `test_completed` - Logs when tests complete

The plugin also triggers custom hooks:
- `example_hello` - Triggered by the hello command
- `example_demo_completed` - Triggered after demo completes

## Plugin Structure

```
example/
├── plugin.json          # Plugin metadata
├── bootstrap.php        # Bootstrap file (loaded on activation)
├── README.md           # Documentation
└── src/
    ├── Plugin.php      # Main plugin class
    └── Commands/
        └── ExampleCommands.php  # Command implementations
```

## Development

To create your own plugin based on this example:

1. Copy this directory structure
2. Rename files and classes appropriately
3. Update `plugin.json` with your metadata
4. Implement your commands in the Commands directory
5. Register hooks in the Plugin class
6. Place in `.rover/plugins/your-plugin-name/`
7. Run `rover plugin:validate your-plugin-name`

## Author

Rover Team

## License

MIT
