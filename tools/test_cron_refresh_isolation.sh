#!/bin/sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)
TMP=$(mktemp -d)
cleanup() { rm -rf "$TMP"; }
trap cleanup EXIT HUP INT TERM

WEBROOT="$TMP/public_html"
SOURCE="$TMP/source"
PRIVATE="$TMP/private"
CALLS="$TMP/calls.log"
mkdir -p "$WEBROOT/templates/pn_natuna_2026" "$SOURCE/templates/pn_natuna_2026" "$SOURCE/tools" "$PRIVATE"
cp "$ROOT/tools/cron-refresh-all.sh" "$SOURCE/tools/cron-refresh-all.sh"
printf '%s\n' 'production-template' > "$WEBROOT/templates/pn_natuna_2026/sentinel.txt"
printf '%s\n' 'production-htaccess' > "$WEBROOT/.htaccess"
printf '%s\n' 'stale-source-template' > "$SOURCE/templates/pn_natuna_2026/sentinel.txt"
printf '%s\n' 'stale-source-htaccess' > "$SOURCE/.htaccess"
printf '%s\n' '[client]' > "$TMP/mysql.cnf"

cat > "$TMP/fake-runner" <<'SH'
#!/bin/sh
printf '%s\n' "$*" >> "$CALLS"
test "${PN_NATUNA_PRIVATE_ROOT:-}" = "$EXPECTED_PRIVATE_ROOT"
SH
chmod 700 "$TMP/fake-runner"

EXPECTED_PRIVATE_ROOT=$PRIVATE
export EXPECTED_PRIVATE_ROOT
export CALLS
PN_NATUNA_JPATH_ROOT="$WEBROOT" \
PN_NATUNA_SOURCE_ROOT="$SOURCE" \
PN_NATUNA_PRIVATE_ROOT="$PRIVATE" \
PHP_BIN="$TMP/fake-runner" \
PYTHON_BIN="$TMP/fake-runner" \
MYSQL_BIN="$TMP/fake-runner" \
MYSQL_DEFAULTS_FILE="$TMP/mysql.cnf" \
DB_NAME=test_database \
sh "$SOURCE/tools/cron-refresh-all.sh" >/dev/null

test "$(cat "$WEBROOT/templates/pn_natuna_2026/sentinel.txt")" = 'production-template'
test "$(cat "$WEBROOT/.htaccess")" = 'production-htaccess'
test "$(wc -l < "$CALLS" | tr -d ' ')" = 6
grep -Fq 'cron-refresh-instansi.php' "$CALLS"
grep -Fq 'cron-refresh-youtube.php' "$CALLS"
grep -Fq 'cron-refresh-sipp.php' "$CALLS"
grep -Fq 'refresh-survey.py' "$CALLS"
grep -Fq 'refresh-dipa.py' "$CALLS"
grep -Fq 'generate-sitemap.php' "$CALLS"
if grep -Eq 'install-admin-login-guard|apply-db-migrations|warm-public-cache|templates/pn_natuna_2026|\.htaccess' "$CALLS"; then
    echo 'FAIL: data cron invoked deployment work' >&2
    exit 1
fi

mkdir "$SOURCE/.git"
if PN_NATUNA_JPATH_ROOT="$WEBROOT" \
    PN_NATUNA_SOURCE_ROOT="$SOURCE" \
    PN_NATUNA_PRIVATE_ROOT="$PRIVATE" \
    PHP_BIN="$TMP/fake-runner" \
    PYTHON_BIN="$TMP/fake-runner" \
    MYSQL_BIN="$TMP/fake-runner" \
    MYSQL_DEFAULTS_FILE="$TMP/mysql.cnf" \
    DB_NAME=test_database \
    sh "$SOURCE/tools/cron-refresh-all.sh" >/dev/null 2>&1; then
    echo 'FAIL: data cron accepted a mutable Git checkout' >&2
    exit 1
fi

echo 'Cron refresh deployment isolation: ok'
