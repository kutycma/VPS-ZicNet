#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

NON_INTERACTIVE="${NON_INTERACTIVE:-0}"
SKIP_APT=0
SKIP_DB_CREATE="${SKIP_DB_CREATE:-0}"
SKIP_ASSETS="${SKIP_ASSETS:-0}"
SKIP_SEED="${SKIP_SEED:-0}"

usage() {
    cat <<'EOF'
Usage: bash install.sh [options]

Options:
  --non-interactive   Use environment variables and defaults, do not prompt.
  --skip-apt          Do not install system packages with apt-get.
  --skip-db-create    Do not create MySQL database/user.
  --skip-assets       Do not run npm install/build.
  --skip-seed         Do not run database seeders.
  -h, --help          Show this help.

Useful environment variables:
  APP_NAME APP_ENV APP_DEBUG APP_URL
  DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
  MYSQL_ADMIN_USER MYSQL_ADMIN_PASSWORD
  ADMIN_NAME ADMIN_EMAIL ADMIN_PASSWORD
  INSTALL_DB_SERVER INSTALL_DEV WEB_USER WEB_GROUP
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --non-interactive) NON_INTERACTIVE=1 ;;
        --skip-apt) SKIP_APT=1 ;;
        --skip-db-create) SKIP_DB_CREATE=1 ;;
        --skip-assets) SKIP_ASSETS=1 ;;
        --skip-seed) SKIP_SEED=1 ;;
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

is_yes() {
    case "${1:-}" in
        y|Y|yes|YES|Yes|1|true|TRUE|True) return 0 ;;
        *) return 1 ;;
    esac
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

    read -r -s -p "$label [leave blank for default]: " input
    printf '\n'
    printf -v "$var_name" '%s' "${input:-$default}"
}

prompt_yes_no() {
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

    if is_yes "$default"; then
        read -r -p "$label [Y/n]: " input
        input="${input:-yes}"
    else
        read -r -p "$label [y/N]: " input
        input="${input:-no}"
    fi
    printf -v "$var_name" '%s' "$input"
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

is_local_db_host() {
    case "${DB_HOST:-}" in
        localhost|127.0.0.1|::1) return 0 ;;
        *) return 1 ;;
    esac
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
        warn "apt-get not found. Skipping OS package installation."
        return
    fi

    if [[ -n "$SUDO" ]] && ! command -v sudo >/dev/null 2>&1; then
        warn "sudo not found. Skipping OS package installation."
        return
    fi

    local base_packages=(
        php-cli php-mbstring php-xml php-curl php-zip php-mysql php-bcmath
        unzip curl git
    )

    log "Installing system packages"
    $SUDO apt-get update
    DEBIAN_FRONTEND=noninteractive $SUDO apt-get -f install -y || true
    DEBIAN_FRONTEND=noninteractive $SUDO apt-get install -y "${base_packages[@]}"

    if is_local_db_host && [[ "${INSTALL_DB_SERVER:-auto}" != "0" && "${INSTALL_DB_SERVER:-auto}" != "false" ]]; then
        DEBIAN_FRONTEND=noninteractive $SUDO apt-get install -y mariadb-server \
            || warn "Could not install mariadb-server automatically."

        $SUDO systemctl enable --now mariadb >/dev/null 2>&1 \
            || $SUDO systemctl enable --now mysql >/dev/null 2>&1 \
            || $SUDO service mariadb start >/dev/null 2>&1 \
            || $SUDO service mysql start >/dev/null 2>&1 \
            || warn "Could not auto-start the database service."
    fi

    if ! command -v mysql >/dev/null 2>&1 && ! command -v mariadb >/dev/null 2>&1; then
        local db_client_installed=0
        local db_client_pkg
        for db_client_pkg in mariadb-client default-mysql-client mysql-client; do
            if DEBIAN_FRONTEND=noninteractive $SUDO apt-get install -y "$db_client_pkg"; then
                db_client_installed=1
                break
            fi
        done

        if [[ "$db_client_installed" != "1" ]]; then
            warn "Could not install a MySQL client package. Install mariadb-client manually if DB creation fails."
        fi
    fi

    if ! command -v node >/dev/null 2>&1; then
        DEBIAN_FRONTEND=noninteractive $SUDO apt-get install -y nodejs \
            || warn "Could not install nodejs automatically."
    fi

    if ! command -v npm >/dev/null 2>&1; then
        DEBIAN_FRONTEND=noninteractive $SUDO apt-get install -y npm \
            || warn "npm is not available. Frontend assets will be skipped unless npm is installed."
    fi
}

install_composer() {
    if command -v composer >/dev/null 2>&1; then
        return
    fi

    require_cmd php
    require_cmd curl

    if [[ -n "$SUDO" ]] && ! command -v sudo >/dev/null 2>&1; then
        fail "Composer is missing and sudo is required to install it into /usr/local/bin."
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

validate_mysql_identifier() {
    [[ "$1" =~ ^[A-Za-z0-9_]+$ ]] || fail "Invalid MySQL identifier: $1. Use only letters, numbers and underscore."
}

sql_escape() {
    printf '%s' "$1" | sed "s/'/''/g"
}

create_database() {
    if [[ "$SKIP_DB_CREATE" == "1" ]]; then
        warn "Skipping database creation by request."
        return
    fi

    local mysql_binary="mysql"
    if ! command -v "$mysql_binary" >/dev/null 2>&1; then
        if command -v mariadb >/dev/null 2>&1; then
            mysql_binary="mariadb"
        else
            fail "Missing mysql/mariadb client. Install mariadb-client or rerun without --skip-apt."
        fi
    fi

    validate_mysql_identifier "$DB_DATABASE"
    validate_mysql_identifier "$DB_USERNAME"

    prompt_value MYSQL_ADMIN_USER "MySQL admin user" "root"
    prompt_secret MYSQL_ADMIN_PASSWORD "MySQL admin password" ""

    log "Creating/updating MySQL database and database user"
    local db_password_escaped
    db_password_escaped="$(sql_escape "$DB_PASSWORD")"

    local mysql_cmd=("$mysql_binary")
    local mysql_args=(-h "$DB_HOST" -P "$DB_PORT" -u "$MYSQL_ADMIN_USER" --protocol=tcp)

    if [[ "$MYSQL_ADMIN_USER" == "root" && -z "${MYSQL_ADMIN_PASSWORD:-}" ]]; then
        if is_local_db_host; then
            mysql_args=(-u "$MYSQL_ADMIN_USER")
            if [[ -n "$SUDO" ]]; then
                mysql_cmd=($SUDO mysql)
            fi
        fi
    fi

    if [[ -n "${MYSQL_ADMIN_PASSWORD:-}" ]]; then
        mysql_args+=("-p${MYSQL_ADMIN_PASSWORD}")
    fi

    "${mysql_cmd[@]}" "${mysql_args[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'%' IDENTIFIED BY '${db_password_escaped}';
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${db_password_escaped}';
ALTER USER '${DB_USERNAME}'@'%' IDENTIFIED BY '${db_password_escaped}';
ALTER USER '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${db_password_escaped}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';
FLUSH PRIVILEGES;
SQL
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

prepare_env() {
    if [[ ! -f .env ]]; then
        log "Creating .env from .env.example"
        cp .env.example .env
    fi

    set_env APP_NAME "$APP_NAME"
    set_env APP_ENV "$APP_ENV"
    set_env APP_DEBUG "$APP_DEBUG"
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

    set_env ADMIN_NAME "$ADMIN_NAME"
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
    if [[ "${INSTALL_DEV:-0}" != "1" ]]; then
        composer_args+=(--no-dev)
    fi

    composer "${composer_args[@]}"
}

build_assets() {
    if [[ "$SKIP_ASSETS" == "1" ]]; then
        return
    fi

    if [[ ! -f package.json ]]; then
        return
    fi

    if ! command -v npm >/dev/null 2>&1; then
        warn "npm not found. Skipping frontend asset build."
        return
    fi

    log "Installing and building frontend assets"
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

    if [[ "$SKIP_SEED" != "1" ]]; then
        export ADMIN_NAME ADMIN_EMAIL ADMIN_PASSWORD
        run_artisan db:seed --force
    fi

    run_artisan storage:link || true

    run_artisan config:cache
    run_artisan view:cache
    run_artisan event:cache || true

    if [[ "${CACHE_ROUTES:-0}" == "1" ]]; then
        run_artisan route:cache || warn "route:cache failed. This app contains a closure route by default."
    else
        warn "Skipping route:cache because routes/web.php contains a closure route."
    fi

    run_artisan queue:restart || true
}

main() {
    log "VPS ZicNet fresh installer"

    prompt_value APP_NAME "App name" "$(env_file_value APP_NAME "VPS ZicNet")"
    prompt_value APP_ENV "App environment" "$(env_file_value APP_ENV "production")"
    prompt_value APP_DEBUG "App debug" "$(env_file_value APP_DEBUG "false")"
    prompt_value APP_URL "App URL" "$(env_file_value APP_URL "http://127.0.0.1")"

    prompt_value DB_HOST "Database host" "$(env_file_value DB_HOST "127.0.0.1")"
    prompt_value DB_PORT "Database port" "$(env_file_value DB_PORT "3306")"
    prompt_value DB_DATABASE "Database name" "$(env_file_value DB_DATABASE "vps_zicnet")"
    prompt_value DB_USERNAME "Database user" "$(env_file_value DB_USERNAME "vps_zicnet")"
    prompt_secret DB_PASSWORD "Database password" "$(env_file_value DB_PASSWORD "$(random_hex)")"

    install_apt_packages
    install_composer
    require_cmd php
    require_cmd composer

    create_database

    log "Database is ready. Create the admin account"
    prompt_value ADMIN_NAME "Admin name" "$(env_file_value ADMIN_NAME "Administrator")"
    prompt_value ADMIN_EMAIL "Admin email" "$(env_file_value ADMIN_EMAIL "admin@zicnet.vn")"
    prompt_secret ADMIN_PASSWORD "Admin password" "$(env_file_value ADMIN_PASSWORD "$(random_hex)")"

    prepare_env
    prepare_directories
    install_php_dependencies
    build_assets
    install_laravel

    printf '\n'
    log "Install completed"
    printf 'URL: %s\n' "$APP_URL"
    printf 'Database: %s@%s:%s/%s\n' "$DB_USERNAME" "$DB_HOST" "$DB_PORT" "$DB_DATABASE"
    if [[ "$SKIP_SEED" != "1" ]]; then
        printf 'Admin: %s\n' "$ADMIN_EMAIL"
        printf 'Admin password: %s\n' "$ADMIN_PASSWORD"
    fi
    printf '\nRun this again safely for normal updates; it uses migrate, not migrate:fresh.\n'
}

main "$@"
