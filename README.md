# php-minimal

Minimal modern PHP development template with Docker, PHP-FPM, Nginx and MariaDB.

This repo is meant to be used as a **starting point for new projects**: copy the
folder, run `./bin/init.sh <project-name>`, and you have an isolated stack with
its own domain, database and (optionally) ports. See
[Using this as a template](#using-this-as-a-template) below.

## Features

- **PHP 8.4** with PHP-FPM
- **Nginx** with HTTPS (self-signed certificate)
- **MariaDB 11**
- **Xdebug 3** for debugging and code coverage
- **PHPUnit 13** for testing
- **PHPStan** (level 8) for static analysis
- **PHP CS Fixer** for code style
- **Layered architecture example** (Domain / Application / Infrastructure / Presentation) with a `Product` entity, repository, service and controller
- **Migrations & seeders** for MariaDB, driven by simple SQL files

## Requirements

- [Docker](https://www.docker.com/) and Docker Compose
- [Composer](https://getcomposer.org/) (optional, runs inside the container)

## Getting started

> Starting a **new** project from this template? Use `./bin/init.sh <project-name>`
> instead of step 1 below — see [Using this as a template](#using-this-as-a-template).
> The steps here are for working with this template repo directly.

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
| `HTTP_PORT` / `HTTPS_PORT` | Host ports for Nginx (default: `80` / `443`). Only change these if you run more than one project stack at once |
| `DB_PORT` | Host port for MariaDB (default: `3306`). Same reasoning as above |
| `DB_HOST` | MariaDB host (default: `mariadb`) |
| `DB_DATABASE` | MariaDB database name |
| `DB_USERNAME` | MariaDB user |
| `DB_PASSWORD` | MariaDB user password |
| `DB_ROOT_PASSWORD` | MariaDB root password |
| `XDEBUG_CLIENT_HOST` | Host IP for Xdebug — see [Debugging with Xdebug](#debugging-with-xdebug) |

### 2. Install dependencies

```bash
composer install
```

### 3. Generate an SSL certificate

Certificates are **not** committed to the repo (`docker/nginx/certs/*.crt`
and `*.key` are gitignored), so Nginx has nothing to serve on a fresh clone
of this template. Generate a self-signed one for `php-minimal.local`:

```bash
mkdir -p docker/nginx/certs
openssl req -x509 -newkey rsa:2048 -nodes -days 825 \
    -keyout docker/nginx/certs/php-minimal.local.key \
    -out docker/nginx/certs/php-minimal.local.crt \
    -subj "/CN=php-minimal.local" \
    -addext "subjectAltName=DNS:php-minimal.local"
```

> If you got here via `./bin/init.sh <project-name>` instead, this step is
> already done for you — it generates the certificate for your project's
> domain automatically.

### 4. Start the stack

```bash
./bin/build.sh   # first run (builds images)
./bin/up.sh      # subsequent runs
```

### 5. Configure local DNS

Add the following entry to your hosts file:

```
127.0.0.1 php-minimal.local
```

- **Linux/macOS:** `/etc/hosts`
- **Windows:** `C:\Windows\System32\drivers\etc\hosts`

### 6. Run migrations and seeders

```bash
./bin/migrate.sh   # create the schema (e.g. the `products` table)
./bin/seed.sh       # load example data
```

The app is available at [https://php-minimal.local](https://php-minimal.local).

> **Note:** If you change `APP_DOMAIN` to something other than
> `php-minimal.local`, update `docker/nginx/default.conf` and regenerate the
> certificate in `docker/nginx/certs/` for the new domain accordingly.

## Project structure

```
.
├── .cursor/                     # Cursor IDE rules and settings
├── .vscode/
│   └── launch.json              # Xdebug launch configuration
├── bin/                         # Helper scripts (Docker wrappers)
├── database/
│   ├── migrations/              # Schema migrations, executed in filename order
│   └── seeds/                   # Seed data, executed in filename order
├── docker/
│   ├── nginx/
│   │   ├── certs/                # Self-signed SSL certificate and key
│   │   └── default.conf
│   └── php/
│       ├── Dockerfile
│       └── conf.d/                # php.ini and xdebug.ini
├── public/                      # Web root (document root)
│   └── index.php
├── src/App/                     # Application source code (PSR-4: App\)
│   ├── Application/
│   │   └── Product/
│   │       └── ProductService.php
│   ├── Domain/
│   │   └── Product/
│   │       ├── Product.php
│   │       └── ProductRepositoryInterface.php
│   ├── Infrastructure/
│   │   ├── Database/
│   │   │   ├── ConnectionFactory.php
│   │   │   ├── DatabaseConfig.php
│   │   │   ├── MigrationRunner.php
│   │   │   └── SeedRunner.php
│   │   └── Product/
│   │       └── ProductRepository.php
│   └── Presentation/
│       └── Product/
│           └── ProductController.php
├── tests/App/                   # PHPUnit tests (PSR-4: Tests\), mirroring src/App
│   ├── Application/Product/
│   ├── Domain/Product/
│   ├── Infrastructure/
│   │   ├── Database/
│   │   └── Product/
│   └── Presentation/Product/
├── .env.example                 # Environment variable template
├── compose.yaml                 # Docker Compose services
├── composer.json
├── phpunit.xml
└── phpstan.neon
```

## Docker services

| Service | Ports |
|---|---|
| PHP-FPM | 9000 (internal only) |
| Nginx | `${HTTP_PORT:-80}`, `${HTTPS_PORT:-443}` |
| MariaDB | `${DB_PORT:-3306}` |

Container names are **not** hardcoded — Compose derives them from
`COMPOSE_PROJECT_NAME` (e.g. `php-graphql-php-1`). This, together with the
`.env`-driven ports above, means two copies of this template can run at the
same time without colliding, as long as each `.env` has a distinct
`COMPOSE_PROJECT_NAME` (and distinct ports, if both need to bind to the host
simultaneously).

Database credentials are taken from `.env` and passed to the MariaDB container via `MARIADB_*` environment variables.

## Database

### Schema

The example schema lives in `database/migrations/` and is applied in filename order by `MigrationRunner`, run via `bin/migrate.php` (invoked through `./bin/migrate.sh`). It tracks executed migrations in a `migration_versions` table so each migration only runs once:

| Migration | Description |
|---|---|
| `001_create_products_table.sql` | Creates the `products` table (`id`, `name`, `created_at`) |
| `002_add_description_to_products.sql` | Adds a nullable `description` column to `products` |

### Seed data

Example data lives in `database/seeds/` and is applied in filename order by `SeedRunner`, run via `bin/seed.php` (invoked through `./bin/seed.sh`):

| Seed | Description |
|---|---|
| `001_insert_products.sql` | Inserts a handful of example products |

Run both with:

```bash
./bin/migrate.sh
./bin/seed.sh
```

## Example domain: Products

The template ships with a small end-to-end example built around a `Product` entity to illustrate the layered architecture:

- **Domain** — `Product` (immutable, self-validating entity) and `ProductRepositoryInterface`
- **Application** — `ProductService`, orchestrating use cases against the repository interface
- **Infrastructure** — `ProductRepository` (PDO/MariaDB implementation), plus `ConnectionFactory`, `DatabaseConfig`, `MigrationRunner` and `SeedRunner`
- **Presentation** — `ProductController`, exposing the data as JSON

### API

| Method | Path | Description |
|---|---|---|
| `GET` | `/` | Returns all products as JSON |

Example response:

```json
[
    {
        "id": 1,
        "name": "Mechanical Keyboard",
        "description": "A tactile mechanical keyboard with hot-swappable switches."
    }
]
```

## Development

All helper scripts run commands inside the PHP container:

| Script | Description |
|---|---|
| `./bin/init.sh` | Bootstrap a copied template into a new project (see [Using this as a template](#using-this-as-a-template)) |
| `./bin/up.sh` | Start containers |
| `./bin/build.sh` | Build images and start containers |
| `./bin/down.sh` | Stop and remove containers |
| `./bin/stop.sh` | Stop containers without removing them (keeps them for a faster `docker compose start`) |
| `./bin/composer.sh` | Run an arbitrary Composer command inside the container, e.g. `./bin/composer.sh require foo/bar` |
| `./bin/migrate.sh` | Run pending database migrations (`bin/migrate.php`) |
| `./bin/seed.sh` | Run database seeders (`bin/seed.php`) |
| `./bin/test.sh` | Run PHPUnit tests |
| `./bin/phpunit.sh` | Run PHPUnit with optional arguments, e.g. `./bin/phpunit.sh --filter=ProductTest` |
| `./bin/coverage.sh` | Generate HTML coverage report in `coverage/` |
| `./bin/analyse.sh` | Run PHPStan static analysis |
| `./bin/cs-fix.sh` | Fix code style with PHP CS Fixer |
| `./bin/autoload.sh` | Regenerate optimized autoloader |
| `./bin/check.sh` | Quality gate: regenerates the autoloader, then runs CS Fixer, PHPStan, tests and coverage in sequence |

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

## Using this as a template

To start a new project from this template:

```bash
cp -r php-minimal php-graphql
cd php-graphql
./bin/init.sh php-graphql              # domain defaults to php-graphql.local
# or: ./bin/init.sh php-graphql graphql.local
```

`bin/init.sh` is safe to re-run and will not overwrite an existing `.env` or
an existing certificate. It:

- creates `.env` from `.env.example` and sets `COMPOSE_PROJECT_NAME`, `APP_DOMAIN`, `PHP_IDE_SERVER_NAME` and `DB_DATABASE`
- renames the package in `composer.json`
- updates `server_name` and the certificate paths in `docker/nginx/default.conf`
- generates a fresh self-signed certificate for the new domain in `docker/nginx/certs/` (requires `openssl` on the host)

Afterwards, follow the usual [Getting started](#getting-started) steps
(hosts file, `composer install`, `./bin/build.sh`, migrate/seed).

**What it does *not* do**, since it's specific to each project:

- Remove the example `Product` domain (`src/App/**/Product`, its tests, and the
  `database/migrations` / `database/seeds` files) — delete these once you start
  building your own domain, or keep them around as a reference.
- Touch `README.md` — update the title/description for the new project yourself.
- Set up multiple projects to run *simultaneously*. The `.env`-driven ports
  (`HTTP_PORT`, `HTTPS_PORT`, `DB_PORT`) let you avoid port collisions between
  two stacks, but each still binds directly to the host. If running several
  projects side by side becomes the norm rather than the exception, a shared
  reverse proxy (e.g. Traefik) fronting all of them on a shared Docker network
  is the more scalable next step.

## License

MIT