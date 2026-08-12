#!/usr/bin/env bash
#
# Bootstraps a fresh project from the php-minimal template.
#
# Usage:
#   ./bin/init.sh <project-name> [domain]
#
# Example:
#   ./bin/init.sh php-graphql
#   ./bin/init.sh php-graphql graphql.local
#
# Run this ONCE, right after copying the template folder for a new project.
# It rewrites everything that is currently hardcoded to "php-minimal" /
# "php-minimal.local": .env, composer.json and the Nginx vhost, and it
# generates a fresh self-signed certificate for the new domain.

set -euo pipefail

OLD_NAME="php-minimal"
OLD_DOMAIN="php-minimal.local"

RED='\033[31m'
GREEN='\033[32m'
YELLOW='\033[33m'
BOLD='\033[1m'
RESET='\033[0m'

fail() {
    echo -e "${RED}${BOLD}Error:${RESET} $1" >&2
    exit 1
}

info() {
    echo -e "${GREEN}[ok]${RESET} $1"
}

warn() {
    echo -e "${YELLOW}[skip]${RESET} $1"
}

PROJECT_NAME="${1:-}"
DOMAIN="${2:-${PROJECT_NAME}.local}"

if [ -z "$PROJECT_NAME" ]; then
    fail "Usage: ./bin/init.sh <project-name> [domain]"
fi

if [[ ! "$PROJECT_NAME" =~ ^[a-z0-9-]+$ ]]; then
    fail "Project name must only contain lowercase letters, digits and hyphens (got: '$PROJECT_NAME')."
fi

if [ ! -f "compose.yaml" ]; then
    fail "compose.yaml not found. Run this script from the project root, e.g. ./bin/init.sh $PROJECT_NAME"
fi

echo -e "${BOLD}Bootstrapping '${PROJECT_NAME}' (domain: ${DOMAIN}) from the php-minimal template${RESET}"
echo ""

# --- .env -------------------------------------------------------------

if [ -f ".env" ]; then
    warn ".env already exists, leaving it untouched. Delete it first if you want it regenerated."
else
    cp .env.example .env
    sed -i \
        -e "s/^COMPOSE_PROJECT_NAME=.*/COMPOSE_PROJECT_NAME=${PROJECT_NAME}/" \
        -e "s/^APP_DOMAIN=.*/APP_DOMAIN=${DOMAIN}/" \
        -e "s/^PHP_IDE_SERVER_NAME=.*/PHP_IDE_SERVER_NAME=${PROJECT_NAME}/" \
        -e "s/^DB_DATABASE=.*/DB_DATABASE=${PROJECT_NAME//-/_}/" \
        .env
    info "Created .env (COMPOSE_PROJECT_NAME, APP_DOMAIN, PHP_IDE_SERVER_NAME, DB_DATABASE)"
    echo -e "        ${YELLOW}Remember to set DB_PASSWORD / DB_ROOT_PASSWORD / XDEBUG_CLIENT_HOST in .env${RESET}"
fi

# --- composer.json ------------------------------------------------------

if [ -f "composer.json" ]; then
    VENDOR="$(grep -m1 '"name"' composer.json | sed -E 's/.*"name": *"([^\/]+)\/.*/\1/')"
    if grep -q "\"name\": *\"${VENDOR}/${OLD_NAME}\"" composer.json; then
        sed -i "s#\"name\": *\"${VENDOR}/${OLD_NAME}\"#\"name\": \"${VENDOR}/${PROJECT_NAME}\"#" composer.json
        info "Updated composer.json package name to ${VENDOR}/${PROJECT_NAME}"
    else
        warn "composer.json package name already up to date (or not '${VENDOR}/${OLD_NAME}')"
    fi
else
    warn "composer.json not found"
fi

# --- Nginx vhost --------------------------------------------------------

NGINX_CONF="docker/nginx/default.conf"

if [ -f "$NGINX_CONF" ]; then
    if grep -q "$OLD_DOMAIN" "$NGINX_CONF"; then
        sed -i "s/${OLD_DOMAIN}/${DOMAIN}/g" "$NGINX_CONF"
        info "Updated ${NGINX_CONF} to serve ${DOMAIN}"
    else
        warn "${NGINX_CONF} already up to date (or does not reference '${OLD_DOMAIN}')"
    fi
else
    warn "${NGINX_CONF} not found"
fi

# --- SSL certificate ------------------------------------------------------

CERTS_DIR="docker/nginx/certs"
CERT_FILE="${CERTS_DIR}/${DOMAIN}.crt"
KEY_FILE="${CERTS_DIR}/${DOMAIN}.key"

mkdir -p "$CERTS_DIR"

if [ -f "$CERT_FILE" ] && [ -f "$KEY_FILE" ]; then
    warn "Certificate for ${DOMAIN} already exists, leaving it untouched."
elif command -v openssl >/dev/null 2>&1; then
    openssl req -x509 -newkey rsa:2048 -nodes -days 825 \
        -keyout "$KEY_FILE" \
        -out "$CERT_FILE" \
        -subj "/CN=${DOMAIN}" \
        -addext "subjectAltName=DNS:${DOMAIN}" \
        >/dev/null 2>&1
    info "Generated self-signed certificate for ${DOMAIN}"

    OLD_CERT="${CERTS_DIR}/${OLD_DOMAIN}.crt"
    OLD_KEY="${CERTS_DIR}/${OLD_DOMAIN}.key"
    if [ -f "$OLD_CERT" ] || [ -f "$OLD_KEY" ]; then
        rm -f "$OLD_CERT" "$OLD_KEY"
        info "Removed leftover certificate for ${OLD_DOMAIN}"
    fi
else
    warn "openssl not found on host — create ${CERT_FILE} / ${KEY_FILE} manually (or via mkcert)."
fi

# --- Done -----------------------------------------------------------------

echo ""
echo -e "${BOLD}${GREEN}Done.${RESET} Next steps:"
echo "  1. Add '127.0.0.1 ${DOMAIN}' to your hosts file"
echo "  2. Review .env (passwords, XDEBUG_CLIENT_HOST)"
echo "  3. composer install"
echo "  4. ./bin/build.sh"
echo "  5. ./bin/migrate.sh && ./bin/seed.sh   # if you keep the example schema/seeds"
echo ""
echo "This script is idempotent for files it already touched — safe to re-run,"
echo "but it will not overwrite an existing .env or an existing certificate."