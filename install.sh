#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

NON_INTERACTIVE="${NON_INTERACTIVE:-0}"
SKIP_APT="${SKIP_APT:-0}"
SKIP_ASSETS="${SKIP_ASSETS:-0}"
INSTALL_DEV="${INSTALL_DEV:-0}"

usage() {
    cat <<'EOF'
Usage: bash install.sh [options]

Simple fresh install:
  1. Enter existing database connection.
  2. Script verifies the database connection.
  3. Script creates a default admin account and prints it at the end.

Options:
  --non-interactive   Use environment variables and defaults, do not prompt.
  --skip-apt          Do not install system packages with apt-get.
  --skip-assets       Do not run npm install/build.
  -h, --help          Show this help.

Environment variables:
  APP_URL
  DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
  ADMIN_EMAIL ADMIN_PASSWORD
  INSTALL_DEV WEB_USER WEB_GROUP
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --non-interactive) NON_INTERACTIVE=1 ;;
        --skip-apt) SKIP_APT=1 ;;
        --skip-assets) SKIP_ASSETS=1 ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage; exit 1 ;;
    esac
    shift
done

log() {
    printf '\033[1;34m==>\033[0m %s\n' "$*"
}

warn() {
    printf '\033[1;33mWARN:\033[0m %s\n' "$*" >&2
}

fail() {
    printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2
    exit 1
}

prompt_value() {
    local var_name="$1"
    local label="$2"
    local default="$3"
    local current="${!var_name-}"
    local input=""

    if [[ -n "$current" ]]; then
        return
    fi

    if [[ "$NON_INTERACTIVE" == "1" || ! -t 0 ]]; then
        printf -v "$var_name" '%s' "$default"
        return
    fi

    read -r -p "$label [$default]: " input
    printf -v "$var_name" '%s' "${input:-$default}"
}

prompt_secret() {
    local var_name="$1"
    local label="$2"
    local default="$3"
    local current="${!var_name-}"
    local input=""

    if [[ -n "$current" ]]; then
        return
    fi

    if [[ "$NON_INTERACTIVE" == "1" || ! -t 0 ]]; then
        printf -v "$var_name" '%s' "$default"
        return
    fi

    read -r -s -p "$label: " input
    printf '\n'
    printf -v "$var_name" '%s' "${input:-$default}"
}

env_file_value() {
    local key="$1"
    local fallback="$2"
    local value=""

    if [[ -f .env ]]; then
        value="$(grep -E "^${key}=" .env | tail -n 1 | cut -d= -f2- || true)"
        value="${value%\"}"
        value="${value#\"}"
        value="${value%\'}"
        value="${value#\'}"
    fi

    printf '%s' "${value:-$fallback}"
}

random_hex() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 16
    else
        date +%s%N | sha256sum | cut -c1-32
    fi
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "Missing command: $1"
}

SUDO=""
if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
    SUDO="sudo"
fi

install_apt_packages() {
    if [[ "$SKIP_APT" == "1" ]]; then
        return
    fi

    if ! command -v apt-get >/dev/null 2>&1; then
        warn "apt-get not found. Skipping package installation."
        return
    fi

    if [[ -n "$SUDO" ]] && ! command -v sudo >/dev/null 2>&1; then
        warn "sudo not found. Skipping package installation."
        return
    fi

    log "Installing required system packages"
    $SUDO apt-get update
    DEBIAN_FRONTEND=noninteractive $SUDO apt-get -f install -y || true
    DEBIAN_FRONTEND=noninteractive $SUDO apt-get install -y \
        php-cli php-mbstring php-xml php-curl php-zip php-mysql php-bcmath \
        unzip curl git
}

install_composer() {
    if command -v composer >/dev/null 2>&1; then
        return
    fi

    require_cmd php
    require_cmd curl

    if [[ -n "$SUDO" ]] && ! command -v sudo >/dev/null 2>&1; then
        fail "Composer is missing and sudo is required to install it."
    fi

    log "Installing Composer"
    local expected actual
    expected="$(php -r "copy('https://composer.github.io/installer.sig', 'php://stdout');")"
    php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    actual="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"

    if [[ "$expected" != "$actual" ]]; then
        rm -f /tmp/composer-setup.php
        fail "Composer installer signature mismatch."
    fi

    php /tmp/composer-setup.php --quiet --install-dir=/tmp --filename=composer
    rm -f /tmp/composer-setup.php
    $SUDO mv /tmp/composer /usr/local/bin/composer
    $SUDO chmod +x /usr/local/bin/composer
}

env_format() {
    local value="$1"
    value="${value//\\/\\\\}"
    value="${value//\"/\\\"}"

    if [[ -z "$value" || "$value" =~ [[:space:]#] ]]; then
        printf '"%s"' "$value"
    else
        printf '%s' "$value"
    fi
}

set_env() {
    local key="$1"
    local raw_value="$2"
    local formatted escaped

    formatted="$(env_format "$raw_value")"
    escaped="$(printf '%s' "${key}=${formatted}" | sed 's/[&|]/\\&/g')"

    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${escaped}|" .env
    else
        printf '%s\n' "${key}=${formatted}" >> .env
    fi
}

test_database_connection() {
    require_cmd php

    log "Checking database connection"
    DB_HOST="$DB_HOST" \
    DB_PORT="$DB_PORT" \
    DB_DATABASE="$DB_DATABASE" \
    DB_USERNAME="$DB_USERNAME" \
    DB_PASSWORD="$DB_PASSWORD" \
    php -r '
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            getenv("DB_HOST"),
            getenv("DB_PORT"),
            getenv("DB_DATABASE")
        );

        try {
            new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (Throwable $e) {
            fwrite(STDERR, "Database connection failed: " . $e->getMessage() . PHP_EOL);
            exit(1);
        }
    ' || fail "Cannot connect to database. Please check DB host, database name, username and password."
}

prepare_env() {
    if [[ ! -f .env ]]; then
        log "Creating .env"
        cp .env.example .env
    fi

    set_env APP_NAME "${APP_NAME:-VPS ZicNet}"
    set_env APP_ENV "${APP_ENV:-production}"
    set_env APP_DEBUG "${APP_DEBUG:-false}"
    set_env APP_URL "$APP_URL"
    set_env LOG_CHANNEL "stack"
    set_env LOG_LEVEL "${LOG_LEVEL:-warning}"

    set_env DB_CONNECTION "mysql"
    set_env DB_HOST "$DB_HOST"
    set_env DB_PORT "$DB_PORT"
    set_env DB_DATABASE "$DB_DATABASE"
    set_env DB_USERNAME "$DB_USERNAME"
    set_env DB_PASSWORD "$DB_PASSWORD"

    set_env CACHE_DRIVER "${CACHE_DRIVER:-file}"
    set_env QUEUE_CONNECTION "${QUEUE_CONNECTION:-sync}"
    set_env SESSION_DRIVER "${SESSION_DRIVER:-file}"
    set_env FILESYSTEM_DRIVER "${FILESYSTEM_DRIVER:-local}"

    set_env ADMIN_NAME "${ADMIN_NAME:-Administrator}"
    set_env ADMIN_EMAIL "$ADMIN_EMAIL"
    set_env ADMIN_PASSWORD "$ADMIN_PASSWORD"

    chmod 600 .env || true
}

prepare_directories() {
    log "Preparing writable directories"
    mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
    chmod -R ug+rw storage bootstrap/cache

    local web_user="${WEB_USER:-www-data}"
    local web_group="${WEB_GROUP:-$web_user}"
    if [[ "${EUID:-$(id -u)}" -eq 0 ]] && id "$web_user" >/dev/null 2>&1; then
        chown -R "$web_user:$web_group" storage bootstrap/cache public || true
    fi
}

install_php_dependencies() {
    require_cmd composer
    log "Installing PHP dependencies"

    local composer_args=(install --prefer-dist --optimize-autoloader --no-interaction)
    if [[ "$INSTALL_DEV" != "1" ]]; then
        composer_args+=(--no-dev)
    fi

    composer "${composer_args[@]}"
}

build_assets() {
    if [[ "$SKIP_ASSETS" == "1" || ! -f package.json ]]; then
        return
    fi

    if ! command -v npm >/dev/null 2>&1; then
        warn "npm not found. Skipping frontend asset build."
        return
    fi

    log "Building frontend assets"
    npm install
    npm run production
}

run_artisan() {
    require_cmd php
    php artisan "$@"
}

install_laravel() {
    log "Running Laravel setup"
    run_artisan config:clear || true
    run_artisan cache:clear || true
    run_artisan view:clear || true

    run_artisan key:generate --force
    run_artisan migrate --force

    export ADMIN_NAME="${ADMIN_NAME:-Administrator}" ADMIN_EMAIL ADMIN_PASSWORD
    run_artisan db:seed --force

    run_artisan storage:link || true
    run_artisan config:cache
    run_artisan view:cache
    run_artisan event:cache || true
    run_artisan queue:restart || true
}

main() {
    log "VPS ZicNet installer"

    prompt_value DB_HOST "Database host" "$(env_file_value DB_HOST "127.0.0.1")"
    prompt_value DB_PORT "Database port" "$(env_file_value DB_PORT "3306")"
    prompt_value DB_DATABASE "Database name" "$(env_file_value DB_DATABASE "vps_zicnet")"
    prompt_value DB_USERNAME "Database username" "$(env_file_value DB_USERNAME "vps_zicnet")"
    prompt_secret DB_PASSWORD "Database password" "$(env_file_value DB_PASSWORD "")"

    APP_URL="${APP_URL:-$(env_file_value APP_URL "http://127.0.0.1")}"
    ADMIN_EMAIL="${ADMIN_EMAIL:-$(env_file_value ADMIN_EMAIL "admin@zicnet.vn")}"
    ADMIN_PASSWORD="${ADMIN_PASSWORD:-$(env_file_value ADMIN_PASSWORD "$(random_hex)")}"

    install_apt_packages
    require_cmd php
    test_database_connection

    prepare_env
    install_composer
    prepare_directories
    install_php_dependencies
    build_assets
    install_laravel

    printf '\n'
    log "Install completed"
    printf 'URL: %s\n' "$APP_URL"
    printf 'Admin email: %s\n' "$ADMIN_EMAIL"
    printf 'Admin password: %s\n' "$ADMIN_PASSWORD"
}

main "$@"
