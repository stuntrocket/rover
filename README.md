# Rover 🚀

**An opinionated Laravel development assistant for teams who value quality and standards.**

Rover is a command-line tool built on Robo that streamlines Laravel development workflows, enforces code quality standards, and manages multiple projects with ease. Perfect for development teams and agencies managing multiple Laravel applications.

## Features

- 🎯 **Opinionated Setup** - Enforce team standards from day one
- 🧹 **Smart Cache Management** - Clear and optimize with single commands
- 🗄️ **Safe Database Operations** - Fresh migrations with built-in safety checks
- ✅ **Intelligent Testing** - Auto-detect Pest or PHPUnit and run tests
- 🎨 **Code Quality** - Integrated Pint linting and formatting
- 📦 **Multi-Project Support** - Manage multiple Laravel projects
- ⚙️ **Team Configuration** - Share standards via `rover.yml`

## Installation

```bash
composer require stuntrocket/rover --dev
```

## Quick Start

```bash
# Initialize Rover in your Laravel project
vendor/bin/robo rover:init

# See what Rover can do
vendor/bin/robo rover:about

# Check your project status
vendor/bin/robo rover:status
```

## Available Commands

### Setup & Configuration

#### `rover:init`
Initialize Rover configuration and optionally install recommended development packages.

```bash
vendor/bin/robo rover:init
```

Creates a `rover.yml` configuration file and offers to install recommended packages like Pint, Pest, and IDE helpers.

#### `rover:status`
Display project status and installed tools.

```bash
vendor/bin/robo rover:status
```

#### `rover:about`
Show Rover version and available commands.

```bash
vendor/bin/robo rover:about
```

---

### Database Operations

#### `rover:fresh`
Drop all tables, run migrations, and seed the database.

```bash
vendor/bin/robo rover:fresh           # With seeding
vendor/bin/robo rover:fresh --no-seed # Without seeding
vendor/bin/robo rover:fresh --force   # Skip confirmation (use carefully!)
```

⚠️ **Safety first**: Automatically checks environment and requires confirmation unless `--force` is used.

#### `rover:db:reset`
Rollback and re-run all migrations.

```bash
vendor/bin/robo rover:db:reset
vendor/bin/robo rover:db:reset --seed
```

#### `rover:db:seed`
Run database seeders.

```bash
vendor/bin/robo rover:db:seed
vendor/bin/robo rover:db:seed --class=UserSeeder
```

#### `rover:db:status`
Show migration status.

```bash
vendor/bin/robo rover:db:status
```

---

### Cache Management

#### `rover:clear`
Clear all Laravel caches (config, route, view, cache, compiled).

```bash
vendor/bin/robo rover:clear
```

Clears:
- Configuration cache
- Route cache
- View cache
- Application cache
- Compiled classes

#### `rover:optimize`
Run all Laravel optimization commands.

```bash
vendor/bin/robo rover:optimize
```

Optimizes:
- Configuration
- Routes
- Views
- Application

#### `rover:refresh`
Clear caches then optimize (useful when switching branches).

```bash
vendor/bin/robo rover:refresh
```

---

### Testing

#### `rover:test`
Smart test runner with automatic Pest/PHPUnit detection.

```bash
vendor/bin/robo rover:test                      # Run all tests
vendor/bin/robo rover:test --filter=UserTest   # Filter tests
vendor/bin/robo rover:test --group=feature     # Run specific group
vendor/bin/robo rover:test --coverage          # With coverage
vendor/bin/robo rover:test --parallel          # Parallel execution (Pest)
```

#### `rover:coverage`
Generate test coverage report.

```bash
vendor/bin/robo rover:coverage
```

#### `rover:test:file`
Run a specific test file.

```bash
vendor/bin/robo rover:test:file tests/Feature/UserTest.php
```

#### `rover:test:list`
List all available test files.

```bash
vendor/bin/robo rover:test:list
```

---

### Code Quality

#### `rover:lint`
Check code style with Laravel Pint.

```bash
vendor/bin/robo rover:lint              # Check and fix
vendor/bin/robo rover:lint --test       # Check only (no fixes)
vendor/bin/robo rover:lint --dirty      # Check uncommitted changes only
```

#### `rover:fix`
Automatically fix code style issues.

```bash
vendor/bin/robo rover:fix
```

#### `rover:check`
Run all pre-commit checks (lint, tests, static analysis).

```bash
vendor/bin/robo rover:check
```

Perfect for CI/CD pipelines and pre-commit hooks!

#### `rover:analyze`
Run static analysis with PHPStan/Larastan.

```bash
vendor/bin/robo rover:analyze
```

#### `rover:ide-helper`
Generate IDE helper files for better autocompletion.

```bash
vendor/bin/robo rover:ide-helper
```

---

### Project Management

#### `rover:list`
List all Laravel projects in current directory.

```bash
vendor/bin/robo rover:list
```

Shows project names and Laravel versions.

---

## Configuration

Rover uses a `rover.yml` configuration file to define team standards:

```yaml
# Team information
team:
  name: StuntRocket
  email: hello@stuntrocket.co

# Code quality settings
quality:
  pint:
    preset: laravel
  testing:
    parallel: false
    coverage: false

# Database settings
database:
  require_confirmation:
    - production
    - staging
  backup:
    path: ./storage/backups
    keep: 7

# Development settings
development:
  packages:
    require-dev:
      - laravel/pint
      - barryvdh/laravel-ide-helper
      - spatie/laravel-ray
      - pestphp/pest
```

Generate a default configuration with:

```bash
vendor/bin/robo rover:init
```

## Recommended Workflow

### Daily Development
```bash
# Start your day
vendor/bin/robo rover:fresh          # Fresh database
vendor/bin/robo rover:test           # Run tests

# Before committing
vendor/bin/robo rover:check          # Pre-commit checks
vendor/bin/robo rover:lint --dirty   # Check your changes
```

### Switching Branches
```bash
vendor/bin/robo rover:refresh        # Clear and optimize
vendor/bin/robo rover:fresh          # Reset database if needed
```

### Code Review
```bash
vendor/bin/robo rover:check          # Run all quality checks
vendor/bin/robo rover:coverage       # Generate coverage report
```

## Aliases

Most commands have short aliases for convenience:

- `rover:fresh` → `fresh`
- `rover:clear` → `clear`
- `rover:optimize` → `optimize`
- `rover:test` → `test`
- `rover:lint` → `lint`
- `rover:fix` → `fix`
- `rover:check` → `check`

## Requirements

- PHP 8.0+
- Laravel 9.0+
- Composer

## Recommended Packages

Rover works best with these packages (automatically offered during `rover:init`):

- `laravel/pint` - Code style fixer
- `pestphp/pest` - Testing framework
- `barryvdh/laravel-ide-helper` - IDE autocompletion
- `nunomaduro/larastan` - Static analysis
- `spatie/laravel-ray` - Debugging

## Roadmap

See [ROADMAP.md](ROADMAP.md) for planned features and development phases.

## Contributing

Contributions are welcome! This tool is designed to evolve with the Laravel community's needs.

## License

MIT License - See LICENSE file for details.

## Credits

Built with ❤️ by [StuntRocket](https://stuntrocket.co) for the Laravel community.
