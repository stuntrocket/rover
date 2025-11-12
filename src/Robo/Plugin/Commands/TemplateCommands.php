<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Template generation for CI/CD pipelines and boilerplate files
 */
class TemplateCommands extends BaseCommand
{
    /**
     * Generate GitHub Actions workflow
     *
     * @command rover:template:github-actions
     */
    public function githubActions(): Result
    {
        $this->requireLaravelProject();

        $this->info('Generating GitHub Actions workflow...');

        // Create .github/workflows directory
        if (!is_dir('.github/workflows')) {
            mkdir('.github/workflows', 0755, true);
        }

        $workflow = <<<'YAML'
name: Laravel CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, dom, fileinfo, mysql
        coverage: xdebug

    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.example', '.env');"

    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

    - name: Generate key
      run: php artisan key:generate

    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache

    - name: Run Pint
      run: vendor/bin/pint --test

    - name: Execute tests
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: testing
        DB_USERNAME: root
        DB_PASSWORD: password
      run: |
        if [ -f vendor/bin/pest ]; then
          vendor/bin/pest --coverage
        else
          vendor/bin/phpunit --coverage-clover coverage.xml
        fi

    - name: Run PHPStan
      run: |
        if [ -f vendor/bin/phpstan ]; then
          vendor/bin/phpstan analyse
        fi

YAML;

        file_put_contents('.github/workflows/laravel.yml', $workflow);

        $this->success('✓ GitHub Actions workflow created at .github/workflows/laravel.yml');

        return Result::success($this);
    }

    /**
     * Generate GitLab CI configuration
     *
     * @command rover:template:gitlab-ci
     */
    public function gitlabCi(): Result
    {
        $this->requireLaravelProject();

        $this->info('Generating GitLab CI configuration...');

        $gitlab = <<<'YAML'
image: php:8.2

variables:
  MYSQL_ROOT_PASSWORD: secret
  MYSQL_DATABASE: testing
  MYSQL_USER: laravel
  MYSQL_PASSWORD: secret
  DB_HOST: mysql

cache:
  paths:
    - vendor/

stages:
  - test
  - deploy

before_script:
  - apt-get update -yqq
  - apt-get install -yqq git libzip-dev libpng-dev
  - docker-php-ext-install pdo_mysql zip gd
  - curl -sS https://getcomposer.org/installer | php
  - php composer.phar install --no-dev --no-scripts

test:
  stage: test
  services:
    - mysql:8.0
  script:
    - cp .env.example .env
    - php artisan key:generate
    - php artisan migrate
    - vendor/bin/pint --test
    - |
      if [ -f vendor/bin/pest ]; then
        vendor/bin/pest
      else
        vendor/bin/phpunit
      fi
  only:
    - main
    - develop
    - merge_requests

deploy_production:
  stage: deploy
  script:
    - echo "Deploy to production server"
    # Add your deployment script here
  only:
    - main
  when: manual

YAML;

        file_put_contents('.gitlab-ci.yml', $gitlab);

        $this->success('✓ GitLab CI configuration created at .gitlab-ci.yml');

        return Result::success($this);
    }

    /**
     * Generate Docker configuration
     *
     * @command rover:template:docker
     */
    public function docker(): Result
    {
        $this->requireLaravelProject();

        $this->info('Generating Docker configuration...');

        // Dockerfile
        $dockerfile = <<<'DOCKERFILE'
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]

DOCKERFILE;

        file_put_contents('Dockerfile', $dockerfile);

        // docker-compose.yml
        $dockerCompose = <<<'YAML'
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: laravel-app
    container_name: laravel-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - laravel

  nginx:
    image: nginx:alpine
    container_name: laravel-nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
    networks:
      - laravel

  db:
    image: mysql:8.0
    container_name: laravel-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
    ports:
      - "3306:3306"
    volumes:
      - dbdata:/var/lib/mysql
    networks:
      - laravel

  redis:
    image: redis:alpine
    container_name: laravel-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - laravel

networks:
  laravel:
    driver: bridge

volumes:
  dbdata:
    driver: local

YAML;

        file_put_contents('docker-compose.yml', $dockerCompose);

        // Nginx config
        if (!is_dir('docker/nginx')) {
            mkdir('docker/nginx', 0755, true);
        }

        $nginxConfig = <<<'NGINX'
server {
    listen 80;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/public;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }
}

NGINX;

        file_put_contents('docker/nginx/laravel.conf', $nginxConfig);

        // .dockerignore
        $dockerignore = <<<'IGNORE'
/node_modules
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.phpunit.result.cache
npm-debug.log
yarn-error.log

IGNORE;

        file_put_contents('.dockerignore', $dockerignore);

        $this->success('✓ Docker configuration created:');
        $this->say('  - Dockerfile');
        $this->say('  - docker-compose.yml');
        $this->say('  - docker/nginx/laravel.conf');
        $this->say('  - .dockerignore');

        $this->info("\nTo start: docker-compose up -d");

        return Result::success($this);
    }

    /**
     * Generate README template
     *
     * @command rover:template:readme
     */
    public function readme(): Result
    {
        $this->requireLaravelProject();

        if (file_exists('README.md')) {
            if (!$this->io()->confirm('README.md already exists. Overwrite?', false)) {
                return Result::cancelled();
            }
        }

        $projectName = basename(getcwd());

        $readme = <<<README
# $projectName

A Laravel application built with quality and standards.

## Features

- Laravel 10.x
- Pest for testing
- Laravel Pint for code style
- Larastan for static analysis
- Managed with Rover

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd $projectName

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Build assets
npm run build

# Start development server
php artisan serve
```

## Development

### Running Tests

```bash
vendor/bin/robo rover:test           # Run all tests
vendor/bin/robo rover:coverage       # With coverage
```

### Code Quality

```bash
vendor/bin/robo rover:lint           # Check code style
vendor/bin/robo rover:fix            # Fix code style
vendor/bin/robo rover:check          # Pre-commit checks
vendor/bin/robo rover:analyze        # Static analysis
```

### Database

```bash
vendor/bin/robo rover:fresh          # Fresh database
vendor/bin/robo rover:db:reset       # Reset database
```

## Deployment

```bash
# Production deployment
./deploy.sh production

# Staging deployment
./deploy.sh staging
```

## Project Structure

```
app/
├── Actions/           # Business logic actions
├── Services/          # Business services
├── Repositories/      # Data repositories
├── DataTransferObjects/  # DTOs
└── Enums/            # Enumerations

tests/
├── Feature/          # Feature tests
└── Unit/             # Unit tests
```

## Contributing

1. Create a feature branch
2. Make your changes
3. Run quality checks: `vendor/bin/robo rover:check`
4. Submit a pull request

## License

Proprietary - All rights reserved

README;

        file_put_contents('README.md', $readme);

        $this->success('✓ README.md created');

        return Result::success($this);
    }

    /**
     * Generate all templates
     *
     * @command rover:template:all
     */
    public function all(): Result
    {
        $this->requireLaravelProject();

        $this->info('Generating all templates...');

        if (!$this->io()->confirm('This will create multiple configuration files. Continue?', true)) {
            return Result::cancelled();
        }

        $results = [];

        // GitHub Actions
        if ($this->io()->confirm('Generate GitHub Actions workflow?', true)) {
            $results[] = $this->githubActions();
        }

        // GitLab CI
        if ($this->io()->confirm('Generate GitLab CI configuration?', false)) {
            $results[] = $this->gitlabCi();
        }

        // Docker
        if ($this->io()->confirm('Generate Docker configuration?', false)) {
            $results[] = $this->docker();
        }

        // README
        if ($this->io()->confirm('Generate README template?', true)) {
            $results[] = $this->readme();
        }

        $this->success("\n✓ All requested templates generated!");

        return Result::success($this);
    }
}
