# Aura Backend

This is the backend API for the Aura project, built with PHP 8.4 and Laravel. It uses Docker (via Laravel Sail) for a seamless development experience across all platforms.

## Requirements

- [Docker](https://docs.docker.com/get-docker/) installed and running.
- [Docker Compose](https://docs.docker.com/compose/install/) (usually included with Docker Desktop).

## Project Setup

Follow the instructions based on your operating system to start the project.

### Windows (Using WSL2)

1. Ensure you have WSL2 installed and configured.
2. Install Docker Desktop and enable the WSL2 backend integration in its settings.
3. Open your WSL2 terminal (e.g., Ubuntu).
4. Navigate to the project directory:
   ```bash
   cd /path/to/Aura/Backend
   ```
5. Copy the environment variables file:
   ```bash
   cp .env.example .env
   ```
6. Start the Docker containers using Laravel Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
7. Generate the application key:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```
8. Run the database migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

### Linux

1. Ensure Docker and Docker Compose are installed.
2. Open your terminal.
3. Navigate to the project directory:
   ```bash
   cd /path/to/Aura/Backend
   ```
4. Copy the environment variables file:
   ```bash
   cp .env.example .env
   ```
5. Install composer dependencies using a small Docker container (if you don't have composer locally):
   ```bash
   docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
   ```
6. Start the Docker containers using Laravel Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
7. Generate the application key:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```
8. Run the database migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

### macOS

1. Install Docker Desktop for Mac.
2. Open your terminal.
3. Navigate to the project directory:
   ```bash
   cd /path/to/Aura/Backend
   ```
4. Copy the environment variables file:
   ```bash
   cp .env.example .env
   ```
5. Start the Docker containers using Laravel Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
6. Generate the application key:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```
7. Run the database migrations:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

## Useful Commands

To run Artisan commands, use Sail:
```bash
./vendor/bin/sail artisan <command>
```

To run tests:
```bash
./vendor/bin/sail artisan test
```

To stop the containers:
```bash
./vendor/bin/sail down
```
