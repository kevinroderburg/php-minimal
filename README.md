# php-minimal

Minimal modern PHP development template with Docker, PHP-FPM, Nginx and MariaDB.

## Features

- **PHP 8.4** with PHP-FPM
- **Nginx** with HTTPS (self-signed certificate)
- **MariaDB 11**
- **Xdebug 3** for debugging and code coverage
- **PHPUnit 13** for testing
- **PHPStan** (level 8) for static analysis
- **PHP CS Fixer** for code style

## Requirements

- [Docker](https://www.docker.com/) and Docker Compose
- [Composer](https://getcomposer.org/) (optional, runs inside the container)

## Getting started

### 1. Clone and configure

```bash
cp .env.example .env
```

Edit `.env` and set your values:

| Variable | Description |
|---|---|
| `COMPOSE_PROJECT_NAME` | Docker Compose project name |
| `APP_DOMAIN` | Local domain (default: `php-minimal.local`) |
| `PHP_IDE_SERVER_NAME` | IDE server name for path mapping (default: `php-minimal`) |
| `DB_DATABASE` | MariaDB database name |
| `DB_USERNAME` | MariaDB user |
| `DB_PASSWORD` | MariaDB user password |
| `DB_ROOT_PASSWORD` | MariaDB root password |
| `XDEBUG_CLIENT_HOST` | Host IP for Xdebug — see [Debugging with Xdebug](#debugging-with-xdebug) |

### 2. Install dependencies

```bash
composer install
```

### 3. Start the stack

```bash
./bin/build.sh   # first run (builds images)
./bin/up.sh      # subsequent runs
```

### 4. Configure local DNS

Add the following entry to your hosts file:

```
127.0.0.1 php-minimal.local
```

- **Linux/macOS:** `/etc/hosts`
- **Windows:** `C:\Windows\System32\drivers\etc\hosts`

The app is available at [https://php-minimal.local](https://php-minimal.local).

> **Note:** The Nginx config and SSL certificates are set up for `php-minimal.local`. If you change `APP_DOMAIN`, update `docker/nginx/default.conf` and regenerate the certificates in `docker/nginx/certs/` accordingly.

## Project structure

```
.
├── bin/                # Helper scripts (Docker wrappers)
├── docker/
│   ├── nginx/          # Nginx config and SSL certificates
│   └── php/            # PHP Dockerfile and configuration
├── public/             # Web root (document root)
│   └── index.php
├── src/App/            # Application source code (PSR-4: App\)
├── tests/              # PHPUnit tests (PSR-4: Tests\)
├── .env.example        # Environment variable template
├── compose.yaml        # Docker Compose services
├── composer.json
├── phpunit.xml
└── phpstan.neon
```

## Docker services

| Service | Container | Ports |
|---|---|---|
| PHP-FPM | `php-minimal-php` | 9000 (internal) |
| Nginx | `php-minimal-nginx` | 80, 443 |
| MariaDB | `php-minimal-mariadb` | 3306 |

Database credentials are taken from `.env` and passed to the MariaDB container via `MARIADB_*` environment variables.

## Development

All helper scripts run commands inside the PHP container:

| Script | Description |
|---|---|
| `./bin/up.sh` | Start containers |
| `./bin/build.sh` | Build and start containers |
| `./bin/down.sh` | Stop containers |
| `./bin/test.sh` | Run PHPUnit tests |
| `./bin/phpunit.sh` | Run PHPUnit with optional arguments |
| `./bin/coverage.sh` | Generate HTML coverage report in `coverage/` |
| `./bin/analyse.sh` | Run PHPStan static analysis |
| `./bin/cs-fix.sh` | Fix code style with PHP CS Fixer |
| `./bin/autoload.sh` | Regenerate optimized autoloader |

Equivalent Composer scripts (inside the container):

```bash
composer test       # PHPUnit
composer coverage   # Coverage report
composer analyse    # PHPStan
composer fix        # PHP CS Fixer
```

## Debugging with Xdebug

Xdebug is pre-configured in the PHP container (port **9003**). The client host is controlled via `XDEBUG_CLIENT_HOST` in `.env` and passed to the container through Docker Compose.

A VS Code / Cursor launch configuration is included in `.vscode/launch.json`.

### Setup

1. Set `XDEBUG_CLIENT_HOST` in `.env` to your host IP:

   | Platform | Typical value |
   |---|---|
   | Docker Desktop (macOS/Windows) | `host.docker.internal` or `192.168.65.254` |
   | WSL2 / Linux | `host.docker.internal` (mapped via `extra_hosts` in `compose.yaml`) |

2. Restart the stack after changing `.env`:

   ```bash
   ./bin/down.sh && ./bin/up.sh
   ```

3. Start the **Listen for Xdebug** debug configuration in your IDE
4. Set a breakpoint and open [https://php-minimal.local](https://php-minimal.local)

`PHP_IDE_SERVER_NAME` in `.env` sets the server name used by `PHP_IDE_CONFIG` inside the container. It should match the server name configured in your IDE for correct path mapping.

## License

MIT
