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

Edit `.env` and set your database passwords and domain:

| Variable | Description |
|---|---|
| `COMPOSE_PROJECT_NAME` | Docker Compose project name |
| `APP_DOMAIN` | Local domain (default: `php-minimal.local`) |
| `DB_DATABASE` | MariaDB database name |
| `DB_USERNAME` | MariaDB user |
| `DB_PASSWORD` | MariaDB user password |
| `DB_ROOT_PASSWORD` | MariaDB root password |
| `XDEBUG_CLIENT_HOST` | Host IP for Xdebug (WSL2/Docker Desktop) |

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

## Development

All helper scripts run commands inside the PHP container:

| Script | Description |
|---|---|
| `./bin/up.sh` | Start containers |
| `./bin/build.sh` | Build and start containers |
| `./bin/down.sh` | Stop containers |
| `./bin/test.sh` | Run PHPUnit tests |
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

Xdebug is pre-configured in the PHP container (port **9003**). A VS Code / Cursor launch configuration is included in `.vscode/launch.json`.

1. Start the Docker stack
2. Start the **Listen for Xdebug** debug configuration in your IDE
3. Set a breakpoint and open [https://php-minimal.local](https://php-minimal.local)

If breakpoints are not hit, adjust `XDEBUG_CLIENT_HOST` in `.env` to match your host IP (e.g. `host.docker.internal` on Docker Desktop or your WSL2 gateway IP).

## License

MIT
