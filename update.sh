#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-}"
SKIP_DEPS="${SKIP_DEPS:-0}"
SKIP_MIGRATE="${SKIP_MIGRATE:-0}"
SKIP_BACKUP="${SKIP_BACKUP:-0}"
INSTALL_DEV="${INSTALL_DEV:-0}"
CACHE_ROUTES="${CACHE_ROUTES:-0}"
PHP_VERSION_TARGET="${PHP_VERSION_TARGET:-7.4}"
PHP_BIN="${PHP_BIN:-}"
PHP_READY=0

usage() {
    cat <<'EOF'
Usage: bash update.sh [options]

This script treats Git remote code as the source of truth. Local code changes
are backed up, then overwritten by the selected remote branch.

Options:
  --branch <name>     Update from this branch instead of the current branch.
  --remote <name>     Git remote name. Default: origin.
  --skip-deps         Skip composer install.
  --skip-migrate      Skip php artisan migrate.
  --skip-backup       Do not backup local changes before overwriting them.
  -h, --help          Show this help.

Useful environment variables:
  GIT_REMOTE GIT_BRANCH SKIP_DEPS SKIP_MIGRATE SKIP_BACKUP
  INSTALL_DEV CACHE_ROUTES WEB_USER WEB_GROUP PHP_BIN PHP_VERSION_TARGET
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --branch)
            [[ $# -ge 2 ]] || { echo "--branch requires a value" >&2; exit 1; }
            GIT_BRANCH="$2"
            shift
            ;;
        --remote)
            [[ $# -ge 2 ]] || { echo "--remote requires a value" >&2; exit 1; }
            GIT_REMOTE="$2"
            shift
            ;;
        --skip-deps) SKIP_DEPS=1 ;;
        --build-assets|--skip-assets) ;;
        --skip-migrate) SKIP_MIGRATE=1 ;;
        --skip-backup) SKIP_BACKUP=1 ;;
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

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "Missing command: $1"
}

select_php_bin() {
    local candidate

    if [[ -n "$PHP_BIN" ]]; then
        command -v "$PHP_BIN" >/dev/null 2>&1 || fail "Missing PHP binary: $PHP_BIN"
        PHP_BIN="$(command -v "$PHP_BIN")"
        return
    fi

    for candidate in "php${PHP_VERSION_TARGET}" php74 php \
        "/www/server/php/74/bin/php" \
        "/usr/bin/php${PHP_VERSION_TARGET}" \
        "/usr/local/bin/php${PHP_VERSION_TARGET}" \
        "/usr/local/php74/bin/php" \
        "/opt/alt/php74/usr/bin/php"; do
        if [[ -x "$candidate" ]]; then
            PHP_BIN="$candidate"
            return
        fi

        if command -v "$candidate" >/dev/null 2>&1; then
            PHP_BIN="$(command -v "$candidate")"
            return
        fi
    done

    fail "Missing PHP ${PHP_VERSION_TARGET}. Install PHP ${PHP_VERSION_TARGET} first."
}

ensure_php_runtime() {
    if [[ "$PHP_READY" == "1" ]]; then
        return
    fi

    select_php_bin

    local version minor
    version="$("$PHP_BIN" -r 'echo PHP_VERSION;')" || fail "Cannot run PHP binary: $PHP_BIN"
    minor="$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"

    if [[ "$minor" != "$PHP_VERSION_TARGET" ]]; then
        fail "This project requires PHP ${PHP_VERSION_TARGET}. Current PHP is ${version} at ${PHP_BIN}. Install PHP ${PHP_VERSION_TARGET} or run with PHP_BIN=/path/to/php${PHP_VERSION_TARGET}."
    fi

    PHP_READY=1
    log "Using PHP ${version} (${PHP_BIN})"
}

run_composer() {
    local composer_path
    ensure_php_runtime

    composer_path="$(command -v composer 2>/dev/null || true)"
    [[ -n "$composer_path" ]] || fail "Missing command: composer"

    "$PHP_BIN" "$composer_path" "$@"
}

SUDO=""
if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
    SUDO="sudo"
fi

APP_WAS_DOWNED=0

restore_app() {
    if [[ "$APP_WAS_DOWNED" == "1" && -f artisan ]]; then
        if ensure_php_runtime >/dev/null 2>&1; then
            "$PHP_BIN" artisan up >/dev/null 2>&1 || true
        fi
    fi
}

trap restore_app EXIT

ensure_git_safe_directory() {
    if git status --short >/dev/null 2>&1; then
        return
    fi

    git config --global --add safe.directory "$ROOT_DIR" >/dev/null 2>&1 || true
}

detect_branch() {
    if [[ -n "$GIT_BRANCH" ]]; then
        return
    fi

    GIT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
    if [[ "$GIT_BRANCH" != "HEAD" ]]; then
        return
    fi

    GIT_BRANCH="$(git symbolic-ref --quiet --short "refs/remotes/${GIT_REMOTE}/HEAD" 2>/dev/null | sed "s#^${GIT_REMOTE}/##" || true)"
    [[ -n "$GIT_BRANCH" ]] || fail "Cannot detect branch. Rerun with: bash update.sh --branch <branch>"
}

backup_local_changes() {
    if [[ "$SKIP_BACKUP" == "1" ]]; then
        warn "Skipping local-change backup by request."
        return
    fi

    if git diff --quiet && git diff --cached --quiet && [[ -z "$(git ls-files --others --exclude-standard)" ]]; then
        return
    fi

    local backup_root backup_dir untracked_list
    backup_root="${UPDATE_BACKUP_DIR:-${ROOT_DIR}/../vps-zicnet-update-backups}"
    backup_dir="${backup_root}/$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$backup_dir"

    log "Backing up local changes to $backup_dir"
    git diff > "${backup_dir}/unstaged.patch" || true
    git diff --cached > "${backup_dir}/staged.patch" || true
    git status --short > "${backup_dir}/status.txt" || true

    untracked_list="${backup_dir}/untracked-files.txt"
    git ls-files --others --exclude-standard > "$untracked_list"
    if [[ -s "$untracked_list" ]]; then
        tar -czf "${backup_dir}/untracked-files.tar.gz" -T "$untracked_list" 2>/dev/null \
            || warn "Could not archive some untracked files."
    fi
}

sync_from_git() {
    require_cmd git
    [[ -d .git ]] || fail "This directory is not a Git repository."

    ensure_git_safe_directory
    detect_branch

    log "Fetching latest code from ${GIT_REMOTE}/${GIT_BRANCH}"
    git fetch --prune "$GIT_REMOTE"

    if ! git rev-parse --verify --quiet "${GIT_REMOTE}/${GIT_BRANCH}" >/dev/null; then
        local remote_head
        remote_head="$(git symbolic-ref --quiet --short "refs/remotes/${GIT_REMOTE}/HEAD" 2>/dev/null | sed "s#^${GIT_REMOTE}/##" || true)"

        if [[ -n "$remote_head" && "$remote_head" != "$GIT_BRANCH" ]] && git rev-parse --verify --quiet "${GIT_REMOTE}/${remote_head}" >/dev/null; then
            warn "Remote branch ${GIT_REMOTE}/${GIT_BRANCH} not found. Using ${GIT_REMOTE}/${remote_head}."
            GIT_BRANCH="$remote_head"
        else
            fail "Remote branch not found: ${GIT_REMOTE}/${GIT_BRANCH}"
        fi
    fi

    backup_local_changes

    log "Overwriting local code with ${GIT_REMOTE}/${GIT_BRANCH}"
    git checkout -f -B "$GIT_BRANCH" "${GIT_REMOTE}/${GIT_BRANCH}"
    git reset --hard "${GIT_REMOTE}/${GIT_BRANCH}"
    git clean -fd
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
    if [[ "$SKIP_DEPS" == "1" ]]; then
        return
    fi

    require_cmd composer
    ensure_php_runtime
    log "Installing PHP dependencies"

    local composer_args=(install --prefer-dist --optimize-autoloader --no-interaction)
    if [[ "$INSTALL_DEV" != "1" ]]; then
        composer_args+=(--no-dev)
    fi

    run_composer "${composer_args[@]}"
}

run_artisan() {
    ensure_php_runtime
    "$PHP_BIN" artisan "$@"
}

prune_legacy_migrations() {
    log "Removing legacy duplicate migrations"

    local migration file

    for migration in database/migrations/*.php; do
        [[ -f "$migration" ]] || continue
        file="$(basename "$migration")"

        case "$file" in
            2014_10_12_000000_create_platform_tables.php|\
            2019_12_14_000001_create_personal_access_tokens_table.php|\
            2026_04_03_000000_create_vps_catalog_tables.php|\
            2026_04_03_000100_create_vps_service_tables.php|\
            2026_04_03_000200_create_billing_tables.php|\
            2026_04_03_000300_create_support_tables.php|\
            2026_04_03_000400_create_settings_and_theme_tables.php|\
            2026_04_03_000500_create_coupon_tables.php)
                ;;
            *)
                rm -f "$migration"
                ;;
        esac
    done
}

run_laravel_update() {
    [[ -f artisan ]] || return

    ensure_php_runtime
    log "Running Laravel update steps"

    if run_artisan down; then
        APP_WAS_DOWNED=1
    fi
    run_artisan config:clear || true
    run_artisan cache:clear || true
    run_artisan route:clear || true
    run_artisan view:clear || true

    if [[ "$SKIP_MIGRATE" != "1" ]]; then
        prune_legacy_migrations
        run_artisan migrate --force
    fi

    run_artisan storage:link || true
    run_artisan config:cache
    run_artisan view:cache
    run_artisan event:cache || true

    if [[ "$CACHE_ROUTES" == "1" ]]; then
        run_artisan route:cache || warn "route:cache failed."
    else
        warn "Skipping route:cache because routes/web.php contains a closure route by default."
    fi

    run_artisan queue:restart || true
    run_artisan up || true
    APP_WAS_DOWNED=0
}

main() {
    log "VPS ZicNet updater"
    sync_from_git
    prepare_directories
    install_php_dependencies
    run_laravel_update
    log "Update completed from ${GIT_REMOTE}/${GIT_BRANCH}"
}

main "$@"
