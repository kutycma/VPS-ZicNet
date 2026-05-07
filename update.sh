#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-}"
SKIP_DEPS="${SKIP_DEPS:-0}"
SKIP_ASSETS="${SKIP_ASSETS:-0}"
SKIP_MIGRATE="${SKIP_MIGRATE:-0}"
SKIP_BACKUP="${SKIP_BACKUP:-0}"
INSTALL_DEV="${INSTALL_DEV:-0}"
CACHE_ROUTES="${CACHE_ROUTES:-0}"

usage() {
    cat <<'EOF'
Usage: bash update.sh [options]

This script treats Git remote code as the source of truth. Local code changes
are backed up, then overwritten by the selected remote branch.

Options:
  --branch <name>     Update from this branch instead of the current branch.
  --remote <name>     Git remote name. Default: origin.
  --skip-deps         Skip composer install.
  --skip-assets       Skip npm install/build.
  --skip-migrate      Skip php artisan migrate.
  --skip-backup       Do not backup local changes before overwriting them.
  -h, --help          Show this help.

Useful environment variables:
  GIT_REMOTE GIT_BRANCH SKIP_DEPS SKIP_ASSETS SKIP_MIGRATE SKIP_BACKUP
  INSTALL_DEV CACHE_ROUTES WEB_USER WEB_GROUP
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
        --skip-assets) SKIP_ASSETS=1 ;;
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

SUDO=""
if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
    SUDO="sudo"
fi

detect_branch() {
    if [[ -n "$GIT_BRANCH" ]]; then
        return
    fi

    GIT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
    if [[ "$GIT_BRANCH" == "HEAD" ]]; then
        fail "Repository is in detached HEAD. Rerun with: bash update.sh --branch <branch>"
    fi
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

    detect_branch

    log "Fetching latest code from ${GIT_REMOTE}/${GIT_BRANCH}"
    git fetch --prune "$GIT_REMOTE"

    if ! git rev-parse --verify --quiet "${GIT_REMOTE}/${GIT_BRANCH}" >/dev/null; then
        fail "Remote branch not found: ${GIT_REMOTE}/${GIT_BRANCH}"
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

    log "Installing and building frontend assets"
    npm install
    npm run production
}

run_artisan() {
    require_cmd php
    php artisan "$@"
}

run_laravel_update() {
    [[ -f artisan ]] || return

    require_cmd php
    log "Running Laravel update steps"

    run_artisan down || true
    run_artisan config:clear || true
    run_artisan cache:clear || true
    run_artisan view:clear || true

    if [[ "$SKIP_MIGRATE" != "1" ]]; then
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
}

main() {
    log "VPS ZicNet updater"
    sync_from_git
    prepare_directories
    install_php_dependencies
    build_assets
    run_laravel_update
    log "Update completed from ${GIT_REMOTE}/${GIT_BRANCH}"
}

main "$@"
