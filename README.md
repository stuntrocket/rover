# Rover 🚀

**An opinionated Laravel development assistant for teams who value quality and standards.**

Rover is a command-line tool built on Robo that streamlines Laravel development workflows, enforces code quality standards, and manages multiple projects with ease. Perfect for development teams and agencies managing multiple Laravel applications.

## Features

- 🎯 **Opinionated Setup** - Enforce team standards from day one
- 🚀 **Project Scaffolding** - Create new Laravel projects with best practices built-in
- 🧹 **Smart Cache Management** - Clear and optimize with single commands
- 🗄️ **Safe Database Operations** - Fresh migrations with built-in safety checks
- 💾 **Database Backup & Restore** - Automated backups with rotation, snapshots, and restore
- 🔒 **Data Anonymization** - Safely anonymize production data for development
- 🛡️ **Migration Safety** - Conflict detection, verification, and safe rollbacks
- ✅ **Intelligent Testing** - Auto-detect Pest or PHPUnit and run tests
- 🎨 **Code Quality** - Integrated Pint linting and formatting
- 📦 **Multi-Project Management** - Manage multiple Laravel projects efficiently
- 🔄 **Batch Operations** - Run commands across all projects simultaneously
- 📊 **Project Insights** - Analytics, statistics, and health monitoring
- 📝 **Log Management** - Tail, filter, and analyze Laravel logs
- 📮 **Queue Management** - Monitor, restart, and manage Laravel queues
- ⏰ **Schedule Management** - Test, verify, and document scheduled commands
- ⚡ **Performance Profiling** - Profile routes, detect N+1 queries, benchmark performance
- 📦 **Package Development** - Scaffold, link, test, and publish Laravel packages
- ⚙️ **Team Configuration** - Share standards via `rover.yml`
- 🔧 **Environment Management** - Smart .env generation and validation
- 🔗 **Git Integration** - Pre-commit hooks and workflow automation
- 📄 **Template Generation** - CI/CD pipelines, Docker, and boilerplate files

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

### Project Scaffolding

#### `rover:new`
Create a new Laravel project with opinionated defaults and team standards.

```bash
vendor/bin/robo rover:new my-project              # Basic setup
vendor/bin/robo rover:new my-project --stack=breeze  # With Laravel Breeze
vendor/bin/robo rover:new my-project --stack=jetstream  # With Jetstream
vendor/bin/robo rover:new my-project --no-pest    # Use PHPUnit instead of Pest
vendor/bin/robo rover:new my-project --no-git     # Skip git initialization
```

Automatically installs and configures:
- Laravel Pint for code style
- Pest for testing
- IDE helper files
- Larastan for static analysis
- Spatie Ray for debugging
- Custom directory structure (Actions, Services, DTOs, etc.)
- Configuration files (.editorconfig, pint.json, phpstan.neon)
- rover.yml for team standards

#### `rover:setup`
Set up an existing Laravel project with Rover standards.

```bash
vendor/bin/robo rover:setup
```

Perfect for adding Rover to existing projects. Installs packages, creates directory structure, and sets up configuration files.

---

### Environment Management

#### `rover:env:validate`
Validate .env file for required variables and test connections.

```bash
vendor/bin/robo rover:env:validate
```

Checks for:
- Required environment variables
- APP_KEY generation
- Production safety settings
- Database connection

#### `rover:env:generate`
Generate .env file with interactive prompts.

```bash
vendor/bin/robo rover:env:generate
vendor/bin/robo rover:env:generate --force  # Overwrite existing
```

#### `rover:env:compare`
Compare .env with .env.example to find missing or extra variables.

```bash
vendor/bin/robo rover:env:compare
```

#### `rover:env:info`
Display environment information (hides sensitive data).

```bash
vendor/bin/robo rover:env:info
```

#### `rover:env:check-secrets`
Check for accidentally exposed secrets in version control.

```bash
vendor/bin/robo rover:env:check-secrets
```

---

### Git Integration

#### `rover:git:hooks`
Install git hooks for automated quality checks.

```bash
vendor/bin/robo rover:git:hooks
```

Installs:
- **pre-commit**: Runs Pint code style checks
- **pre-push**: Runs test suite
- **commit-msg**: Validates commit message format

#### `rover:git:hooks:remove`
Remove installed git hooks.

```bash
vendor/bin/robo rover:git:hooks:remove
```

#### `rover:git:status-all`
Show git status for all Laravel projects in current directory.

```bash
vendor/bin/robo rover:git:status-all
```

#### `rover:git:gitignore`
Generate Laravel .gitignore file.

```bash
vendor/bin/robo rover:git:gitignore
```

---

### Template Generation

#### `rover:template:github-actions`
Generate GitHub Actions workflow for CI/CD.

```bash
vendor/bin/robo rover:template:github-actions
```

Creates `.github/workflows/laravel.yml` with:
- Code style checks
- Test execution
- Static analysis
- MySQL service container

#### `rover:template:gitlab-ci`
Generate GitLab CI configuration.

```bash
vendor/bin/robo rover:template:gitlab-ci
```

#### `rover:template:docker`
Generate Docker configuration (Dockerfile, docker-compose.yml, nginx config).

```bash
vendor/bin/robo rover:template:docker
```

#### `rover:template:readme`
Generate README template with project documentation structure.

```bash
vendor/bin/robo rover:template:readme
```

#### `rover:template:all`
Generate all templates with interactive prompts.

```bash
vendor/bin/robo rover:template:all
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

### Database Backup & Management

#### `rover:db:backup`
Create timestamped database backups with automatic rotation.

```bash
vendor/bin/robo rover:db:backup                    # Auto timestamp
vendor/bin/robo rover:db:backup --name=before-deploy  # Custom name
vendor/bin/robo rover:db:backup --no-compress      # Uncompressed
```

Features:
- Supports MySQL, PostgreSQL, and SQLite
- Automatic gzip compression
- Keeps last 7 backups by default
- Stored in `storage/backups/database/`

#### `rover:db:backups`
List all available database backups.

```bash
vendor/bin/robo rover:db:backups
```

Shows filename, size, and creation date for each backup.

#### `rover:db:restore`
Restore database from a backup.

```bash
vendor/bin/robo rover:db:restore           # Interactive selection
vendor/bin/robo rover:db:restore 1         # Restore backup #1
vendor/bin/robo rover:db:restore backup.sql.gz  # Specific file
vendor/bin/robo rover:db:restore --force   # Skip confirmation
```

⚠️ **Warning**: This replaces your current database!

#### `rover:db:backup:clean`
Delete old backups, keeping the most recent ones.

```bash
vendor/bin/robo rover:db:backup:clean              # Keep 7 backups
vendor/bin/robo rover:db:backup:clean --keep=10    # Keep 10 backups
```

#### `rover:db:snapshot`
Quick snapshot for testing (uses "latest" naming).

```bash
vendor/bin/robo rover:db:snapshot              # Create snapshot
vendor/bin/robo rover:db:snapshot:restore      # Restore snapshot
```

Perfect for:
- Testing destructive operations
- Quick before/after comparisons
- Experimenting with data

#### `rover:db:anonymize`
Anonymize sensitive user data for safe development/staging use.

```bash
vendor/bin/robo rover:db:anonymize
```

Anonymizes:
- Email addresses (user1@example.com, user2@example.com)
- Passwords (set to default hash)
- Names (User 1, User 2)
- Tokens cleared

⚠️ **Production Safety**: Automatically blocked in production environments.

#### `rover:db:sync`
Sync database from remote environment (requires configuration).

```bash
vendor/bin/robo rover:db:sync staging
vendor/bin/robo rover:db:sync production --anonymize
```

Provides manual instructions for database synchronization.

---

### Migration Safety Tools

#### `rover:migrate:check`
Check for migration conflicts (duplicate timestamps, naming issues).

```bash
vendor/bin/robo rover:migrate:check
```

Detects:
- Duplicate migration timestamps
- Naming conflicts
- Potential merge issues

#### `rover:migrate:verify`
Comprehensive migration verification before running.

```bash
vendor/bin/robo rover:migrate:verify
```

Checks for:
- Migration conflicts
- Risky operations (dropping columns/tables)
- Safety concerns

#### `rover:migrate:rollback-safe`
Safe rollback with preview and confirmation.

```bash
vendor/bin/robo rover:migrate:rollback-safe --step=1
vendor/bin/robo rover:migrate:rollback-safe --step=3 --force
```

Features:
- Shows migrations to be rolled back
- Requires confirmation (unless --force)
- Blocks production rollbacks

#### `rover:migrate:history`
View migration history and pending migrations.

```bash
vendor/bin/robo rover:migrate:history
```

#### `rover:make:migration`
Create migration with conflict checking.

```bash
vendor/bin/robo rover:make:migration create_posts_table --create=posts
vendor/bin/robo rover:make:migration add_status_to_users --table=users
```

Automatically checks for naming conflicts before creation.

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

### Workspace Management

#### `rover:health`
Run comprehensive health checks across all Laravel projects.

```bash
vendor/bin/robo rover:health
```

Checks for:
- Missing dependencies
- Environment configuration
- Storage permissions
- Git status
- Outdated packages

#### `rover:switch`
Quick switch between Laravel projects with interactive selection.

```bash
vendor/bin/robo rover:switch              # Interactive selection
vendor/bin/robo rover:switch my-project   # Direct switch
```

#### `rover:workspace:status`
Detailed overview of all projects in workspace.

```bash
vendor/bin/robo rover:workspace:status
```

Shows Laravel version, git branch, environment, and dependency status.

#### `rover:workspace:versions`
Compare Laravel versions across all projects.

```bash
vendor/bin/robo rover:workspace:versions
```

---

### Batch Operations

#### `rover:run-all`
Execute any command across all Laravel projects.

```bash
vendor/bin/robo rover:run-all "php artisan migrate"
vendor/bin/robo rover:run-all "git pull" --continue
```

#### `rover:update-all`
Update composer dependencies in all projects.

```bash
vendor/bin/robo rover:update-all
vendor/bin/robo rover:update-all --dev        # Dev dependencies only
vendor/bin/robo rover:update-all --continue   # Continue on failures
```

#### `rover:test-all`
Run test suites across all projects.

```bash
vendor/bin/robo rover:test-all
vendor/bin/robo rover:test-all --continue  # Continue even if tests fail
```

#### `rover:git:pull-all`
Pull latest changes in all git repositories.

```bash
vendor/bin/robo rover:git:pull-all
```

Automatically skips repositories with uncommitted changes.

#### `rover:clear-all`
Clear all caches across all Laravel projects.

```bash
vendor/bin/robo rover:clear-all
```

#### `rover:install-all`
Run composer install in all projects.

```bash
vendor/bin/robo rover:install-all
```

---

### Project Insights & Analytics

#### `rover:insights:stats`
Generate detailed statistics for a project.

```bash
vendor/bin/robo rover:insights:stats              # Current project
vendor/bin/robo rover:insights:stats my-project   # Specific project
```

Shows:
- Lines of code
- File counts (controllers, models, migrations, tests)
- Dependency counts
- Git statistics
- Test ratio

#### `rover:insights:dependencies`
Compare dependency versions across all projects.

```bash
vendor/bin/robo rover:insights:dependencies
```

Identifies version inconsistencies for standardization.

#### `rover:insights:security`
Security audit across all projects.

```bash
vendor/bin/robo rover:insights:security
```

Runs composer audit to detect known vulnerabilities.

#### `rover:insights:outdated`
Check for outdated packages in all projects.

```bash
vendor/bin/robo rover:insights:outdated
```

#### `rover:insights:report`
Generate comprehensive workspace report.

```bash
vendor/bin/robo rover:insights:report
```

Provides overview of all projects including Laravel versions, git status, and testing coverage.

---

### Log Management

#### `rover:logs`
Tail and filter Laravel logs.

```bash
vendor/bin/robo rover:logs                        # Last 50 lines
vendor/bin/robo rover:logs --lines=100            # Last 100 lines
vendor/bin/robo rover:logs --follow               # Follow mode (tail -f)
vendor/bin/robo rover:logs --level=error          # Filter by level
vendor/bin/robo rover:logs --grep="UserController" # Search pattern
```

Supported log levels: emergency, alert, critical, error, warning, notice, info, debug

#### `rover:logs:clear`
Clear Laravel log file.

```bash
vendor/bin/robo rover:logs:clear
vendor/bin/robo rover:logs:clear --force
```

#### `rover:logs:stats`
Show log file statistics.

```bash
vendor/bin/robo rover:logs:stats
```

Displays:
- File size
- Total lines
- Errors by level (ERROR, WARNING, etc.)

#### `rover:logs:errors`
Find recent errors quickly.

```bash
vendor/bin/robo rover:logs:errors                 # Last 10 errors
vendor/bin/robo rover:logs:errors --lines=20      # Last 20 errors
```

#### `rover:logs:archive`
Archive and clear logs.

```bash
vendor/bin/robo rover:logs:archive
```

Archives to `storage/logs/archive/` with gzip compression.

---

### Queue Management

#### `rover:queue:monitor`
Monitor queue status and workers.

```bash
vendor/bin/robo rover:queue:monitor
vendor/bin/robo rover:queue:monitor --queue=emails
```

Shows:
- Failed job count
- Running workers
- Queue statistics

#### `rover:queue:clear`
Clear all failed jobs.

```bash
vendor/bin/robo rover:queue:clear
vendor/bin/robo rover:queue:clear --force
```

#### `rover:queue:retry-all`
Retry all failed jobs.

```bash
vendor/bin/robo rover:queue:retry-all
```

#### `rover:queue:failed`
List failed jobs.

```bash
vendor/bin/robo rover:queue:failed
```

#### `rover:queue:restart`
Gracefully restart queue workers.

```bash
vendor/bin/robo rover:queue:restart
```

Workers finish current jobs then restart.

#### `rover:queue:work`
Run queue worker in development.

```bash
vendor/bin/robo rover:queue:work
vendor/bin/robo rover:queue:work --queue=emails --tries=5
```

---

### Schedule Management

#### `rover:schedule:list`
List all scheduled commands.

```bash
vendor/bin/robo rover:schedule:list
```

#### `rover:schedule:run`
Run scheduled commands manually.

```bash
vendor/bin/robo rover:schedule:run
```

#### `rover:schedule:test`
Test scheduled commands immediately.

```bash
vendor/bin/robo rover:schedule:test                   # All commands
vendor/bin/robo rover:schedule:test "emails:send"     # Specific command
```

#### `rover:schedule:work`
Run scheduler in foreground (development).

```bash
vendor/bin/robo rover:schedule:work
```

#### `rover:schedule:check`
Verify cron setup.

```bash
vendor/bin/robo rover:schedule:check
```

Checks:
- Scheduled commands configuration
- Cron job setup
- Recent schedule runs

#### `rover:schedule:docs`
Generate schedule documentation.

```bash
vendor/bin/robo rover:schedule:docs
```

Creates `docs/SCHEDULE.md` with all scheduled commands.

---

### Performance & Profiling

#### `rover:profile`
Profile application performance.

```bash
vendor/bin/robo rover:profile                    # Profile homepage
vendor/bin/robo rover:profile /api/users        # Profile specific route
```

Runs multiple requests and shows:
- Average response time
- Min/max response time
- Performance rating

#### `rover:n+1`
Detect N+1 query problems.

```bash
vendor/bin/robo rover:n+1
```

- Checks for Telescope/Debugbar
- Scans code for common N+1 patterns
- Provides optimization suggestions

#### `rover:benchmark`
Benchmark database performance.

```bash
vendor/bin/robo rover:benchmark
```

Tests:
- Simple queries
- Database connection
- Overall performance

#### `rover:cache:warm`
Warm all application caches for optimal performance.

```bash
vendor/bin/robo rover:cache:warm
```

Caches:
- Configuration
- Routes
- Views
- Events

#### `rover:metrics`
Show application metrics.

```bash
vendor/bin/robo rover:metrics
```

Displays:
- PHP and Laravel versions
- Cache sizes
- Log file size
- Database size

---

### Package Development

#### `rover:package:init`
Create a new Laravel package with complete structure.

```bash
vendor/bin/robo rover:package:init vendor/package-name
vendor/bin/robo rover:package:init vendor/package-name --path=packages
```

Creates:
- Complete package structure (src, tests, config, migrations, views)
- composer.json with proper autoloading
- Service provider
- README and LICENSE templates
- PHPUnit/Pest configuration
- GitHub Actions workflow
- .gitignore

#### `rover:package:link`
Link package for local development (symlink to vendor).

```bash
# From Laravel project
vendor/bin/robo rover:package:link ../packages/vendor/package

# Or from package directory
cd packages/vendor/package
vendor/bin/robo rover:package:link
```

Automatically:
- Adds path repository to composer.json
- Creates symlink in vendor/
- Enables live development

#### `rover:package:unlink`
Unlink package and remove from project.

```bash
vendor/bin/robo rover:package:unlink vendor/package-name
```

#### `rover:package:test`
Run package tests in isolation.

```bash
vendor/bin/robo rover:package:test
vendor/bin/robo rover:package:test ../packages/vendor/package
```

#### `rover:package:publish`
Verify package is ready for publishing.

```bash
vendor/bin/robo rover:package:publish
```

Checks:
- composer.json completeness
- README existence
- LICENSE file
- Test coverage
- Documentation quality

#### `rover:package:docs`
Generate package documentation.

```bash
vendor/bin/robo rover:package:docs
```

Creates README.md template with:
- Installation instructions
- Usage examples
- Testing commands
- Contributing guidelines

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

### Starting a New Project
```bash
# Create project with Rover standards
vendor/bin/robo rover:new my-awesome-app --stack=breeze

cd my-awesome-app

# Set up environment
vendor/bin/robo rover:env:generate

# Install git hooks
vendor/bin/robo rover:git:hooks

# Generate CI/CD pipeline
vendor/bin/robo rover:template:github-actions

# Start developing!
vendor/bin/robo rover:fresh
vendor/bin/robo rover:test
```

### Adding Rover to Existing Project
```bash
# Install Rover
composer require stuntrocket/rover --dev

# Set up with opinionated defaults
vendor/bin/robo rover:setup

# Validate environment
vendor/bin/robo rover:env:validate

# Install git hooks
vendor/bin/robo rover:git:hooks
```

### Daily Development
```bash
# Start your day
vendor/bin/robo rover:fresh          # Fresh database
vendor/bin/robo rover:test           # Run tests

# Before committing (automated with git hooks)
vendor/bin/robo rover:check          # Pre-commit checks
vendor/bin/robo rover:lint --dirty   # Check your changes
```

### Switching Branches
```bash
vendor/bin/robo rover:refresh        # Clear and optimize
vendor/bin/robo rover:fresh          # Reset database if needed
vendor/bin/robo rover:env:compare    # Check for new env variables
```

### Code Review
```bash
vendor/bin/robo rover:check          # Run all quality checks
vendor/bin/robo rover:coverage       # Generate coverage report
vendor/bin/robo rover:analyze        # Static analysis
```

### Managing Multiple Projects (Agencies & Teams)
```bash
# Quick health check on all projects
vendor/bin/robo rover:health

# Get workspace overview
vendor/bin/robo rover:workspace:status

# Run tests across all projects
vendor/bin/robo rover:test-all

# Update all projects
vendor/bin/robo rover:update-all

# Pull latest changes in all repos
vendor/bin/robo rover:git:pull-all

# Security audit across workspace
vendor/bin/robo rover:insights:security

# Generate workspace report
vendor/bin/robo rover:insights:report

# Run custom command on all projects
vendor/bin/robo rover:run-all "php artisan migrate"
```

### Weekly Maintenance
```bash
# Check for outdated packages
vendor/bin/robo rover:insights:outdated

# Security audit
vendor/bin/robo rover:insights:security

# Check dependency consistency
vendor/bin/robo rover:insights:dependencies

# Compare Laravel versions
vendor/bin/robo rover:workspace:versions

# Create weekly backups
vendor/bin/robo rover:db:backup --name=weekly
vendor/bin/robo rover:db:backup:clean --keep=4  # Keep 4 weekly backups
```

### Database Operations & Safety
```bash
# Before risky operations - create snapshot
vendor/bin/robo rover:db:snapshot

# Test the risky operation
php artisan some:risky:command

# If something goes wrong, restore immediately
vendor/bin/robo rover:db:snapshot:restore

# Before deployment - backup and verify migrations
vendor/bin/robo rover:db:backup --name=before-deploy
vendor/bin/robo rover:migrate:verify
vendor/bin/robo rover:migrate:check

# After pulling production data - anonymize for safety
vendor/bin/robo rover:db:sync production
vendor/bin/robo rover:db:anonymize

# Safe migration rollback
vendor/bin/robo rover:migrate:rollback-safe --step=1
```

### Debugging & Troubleshooting
```bash
# Check logs for errors
rover:logs:errors                        # Recent errors
rover:logs --level=error --follow        # Watch errors in real-time

# Monitor queues
rover:queue:monitor                      # Queue status
rover:queue:failed                       # View failed jobs
rover:queue:retry-all                    # Retry failed jobs

# Check schedule
rover:schedule:check                     # Verify cron setup
rover:schedule:test                      # Test scheduled commands

# Profile performance issues
rover:profile /api/slow-endpoint         # Profile specific route
rover:n+1                                # Detect N+1 queries
rover:benchmark                          # Database performance
```

### Performance Optimization
```bash
# Before deployment - optimize
rover:cache:warm                         # Warm all caches
rover:optimize                           # Optimize Laravel
rover:profile /                          # Verify performance

# Monitor and improve
rover:metrics                            # Check resource usage
rover:n+1                                # Find query issues
rover:logs:stats                         # Analyze log patterns
```

### Queue & Background Jobs
```bash
# Development workflow
rover:queue:work                         # Run worker locally

# Monitoring
rover:queue:monitor                      # Check queue health
rover:queue:failed                       # View failures

# Maintenance
rover:queue:restart                      # Restart workers
rover:queue:clear                        # Clear failed jobs
rover:queue:retry-all                    # Retry all failed
```

### Package Development
```bash
# Create new package
rover:package:init acme/awesome-package

# Set up local development
cd packages/acme/awesome-package
composer install
rover:package:test                       # Run tests

# Link to Laravel project
cd ../../../
rover:package:link packages/acme/awesome-package

# Develop with live reload
# Edit package code, changes reflect immediately

# Before publishing
rover:package:publish                    # Check readiness
rover:package:test                       # Final tests
rover:package:docs                       # Generate docs

# Publish to Packagist
git tag v1.0.0
git push --tags
# Submit to packagist.org
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
