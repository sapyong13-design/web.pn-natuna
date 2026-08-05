#!/bin/sh
set -eu

: "${PN_NATUNA_JPATH_ROOT:?set PN_NATUNA_JPATH_ROOT}"
: "${PN_NATUNA_SOURCE_ROOT:?set PN_NATUNA_SOURCE_ROOT}"
: "${PHP_BIN:=/usr/local/bin/php}"
: "${PYTHON_BIN:=/usr/bin/python3}"
: "${MYSQL_BIN:=/usr/bin/mysql}"
: "${MYSQL_DEFAULTS_FILE:?set MYSQL_DEFAULTS_FILE}"
: "${DB_NAME:?set DB_NAME}"
: "${PN_NATUNA_PRIVATE_ROOT:=$(dirname "$PN_NATUNA_JPATH_ROOT")/private}"

export PN_NATUNA_JPATH_ROOT MYSQL_BIN MYSQL_DEFAULTS_FILE DB_NAME
export PN_NATUNA_LOG_FILE="$PN_NATUNA_PRIVATE_ROOT/logs/instansi-refresh.log"
export PN_NATUNA_YOUTUBE_LOG_FILE="$PN_NATUNA_PRIVATE_ROOT/logs/youtube-refresh.log"

mkdir -p "$PN_NATUNA_JPATH_ROOT/cache" "$PN_NATUNA_JPATH_ROOT/images/surveys" "$PN_NATUNA_PRIVATE_ROOT/logs"

# Synchronize tracked presentation assets after the operator updates the private
# checkout. Never copy configuration.php, runtime cache, uploads, or nested apps.
case "$PN_NATUNA_JPATH_ROOT" in
    */public_html) ;;
    *) printf 'ERROR: production refresh target must be public_html: %s\n' "$PN_NATUNA_JPATH_ROOT" >&2; exit 77 ;;
esac

sync_tree() {
    source=$1
    target=$2
    test -d "$source" || { printf 'ERROR: source directory missing: %s\n' "$source" >&2; return 2; }
    mkdir -p "$target"
    cp -R "$source/." "$target/"
}

sync_tree "$PN_NATUNA_SOURCE_ROOT/templates/pn_natuna_2026" "$PN_NATUNA_JPATH_ROOT/templates/pn_natuna_2026"
cp "$PN_NATUNA_SOURCE_ROOT/robots.txt" "$PN_NATUNA_JPATH_ROOT/robots.txt"
cp "$PN_NATUNA_SOURCE_ROOT/.htaccess" "$PN_NATUNA_JPATH_ROOT/.htaccess"
printf '%s aturan crawl dan redirect tersinkron\n' "$(date -u +%FT%TZ)"
printf '%s aset template tersinkron\n' "$(date -u +%FT%TZ)"

run() {
    name=$1
    shift
    if "$@"; then
        printf '%s %s berhasil\n' "$(date -u +%FT%TZ)" "$name"
    else
        code=$?
        printf '%s %s gagal (exit %s)\n' "$(date -u +%FT%TZ)" "$name" "$code" >&2
        return "$code"
    fi
}

status=0
run login-guard "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/install-admin-login-guard.php" || status=1
run instansi "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/cron-refresh-instansi.php" || status=1
run youtube "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-youtube.php" || status=1
run sipp "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-sipp.php" || status=1
run survei "$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/refresh-survey.py" || status=1
run dipa "$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/refresh-dipa.py" || status=1
run sitemap "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/generate-sitemap.php" || status=1
run migrations "$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/apply-db-migrations.py" --mysql "$MYSQL_BIN" --mysql-defaults-file "$MYSQL_DEFAULTS_FILE" --database "$DB_NAME" || status=1
exit "$status"
