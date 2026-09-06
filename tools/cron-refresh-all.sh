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

# Refresh data only. Code, administrator guards, and schema migrations belong to
# the guarded deployment flow; cron must never overwrite a running release.
case "$PN_NATUNA_JPATH_ROOT" in
    */public_html) ;;
    *) printf 'ERROR: production refresh target must be public_html: %s\n' "$PN_NATUNA_JPATH_ROOT" >&2; exit 77 ;;
esac


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
run instansi "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/cron-refresh-instansi.php" || status=1
run youtube "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-youtube.php" || status=1
run sipp "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/cron-refresh-sipp.php" || status=1
run survei "$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/refresh-survey.py" || status=1
run dipa "$PYTHON_BIN" "$PN_NATUNA_SOURCE_ROOT/tools/refresh-dipa.py" || status=1
run sitemap "$PHP_BIN" -f "$PN_NATUNA_SOURCE_ROOT/tools/generate-sitemap.php" || status=1
# ponytail: warming skipped while every public HTML response carries Joomla's session token; restore only after token-free routes prove miss then hit.
exit "$status"
