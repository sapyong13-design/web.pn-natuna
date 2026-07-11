#!/bin/sh
# Private CLI only. Run outside public_html; credentials come from a mode-0600 env file.
set -eu
umask 077

CONFIG=${1:-"$HOME/.config/pn-natuna/backup.env"}
case "$CONFIG" in /*) ;; *) echo "ERROR: config path must be absolute" >&2; exit 64;; esac
[ -f "$CONFIG" ] || { echo "ERROR: config missing" >&2; exit 66; }
mode=$(stat -c '%a' "$CONFIG" 2>/dev/null || stat -f '%Lp' "$CONFIG" 2>/dev/null || echo unknown)
[ "$mode" = 600 ] || { echo "ERROR: config must have mode 600" >&2; exit 77; }
# shellcheck disable=SC1090
. "$CONFIG"

: "${BACKUP_DIR:?BACKUP_DIR required}"
: "${DB_HOST:?DB_HOST required}"
: "${DB_NAME:?DB_NAME required}"
: "${DB_USER:?DB_USER required}"
: "${DB_PASSWORD:?DB_PASSWORD required}"
RETENTION_DAYS=${RETENTION_DAYS:-14}
MYSQLDUMP_BIN=${MYSQLDUMP_BIN:-mysqldump}
GZIP_BIN=${GZIP_BIN:-gzip}

case "$BACKUP_DIR" in /*) ;; *) echo "ERROR: BACKUP_DIR must be absolute" >&2; exit 64;; esac
case "$BACKUP_DIR" in *public_html*|*www*|*htdocs*) echo "ERROR: backup directory appears web-accessible" >&2; exit 77;; esac
case "$RETENTION_DAYS" in ''|*[!0-9]*) echo "ERROR: RETENTION_DAYS must be numeric" >&2; exit 64;; esac

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"
lock="$BACKUP_DIR/.backup.lock"
if ! mkdir "$lock" 2>/dev/null; then
  echo "ERROR: backup already running" >&2
  exit 75
fi
opt=$(mktemp "${TMPDIR:-/tmp}/pn-db.XXXXXX")
partial=''
cleanup() {
  [ -z "$partial" ] || rm -f "$partial"
  rm -f "$opt"
  rmdir "$lock" 2>/dev/null || true
}
trap cleanup EXIT HUP INT TERM
chmod 600 "$opt"
cat >"$opt" <<EOF
[client]
host=$DB_HOST
user=$DB_USER
password=$DB_PASSWORD
EOF

stamp=$(date -u '+%Y%m%dT%H%M%SZ')
base="$BACKUP_DIR/${DB_NAME}-${stamp}.sql.gz"
partial="$base.part"
"$MYSQLDUMP_BIN" --defaults-extra-file="$opt" --single-transaction --quick --skip-lock-tables --routines --triggers --events --hex-blob --default-character-set=utf8mb4 -- "$DB_NAME" | "$GZIP_BIN" -9 >"$partial"
[ -s "$partial" ] || { echo "ERROR: empty backup" >&2; exit 74; }
"$GZIP_BIN" -t "$partial"
mv "$partial" "$base"
partial=''
chmod 600 "$base"
if command -v sha256sum >/dev/null 2>&1; then
  (cd "$BACKUP_DIR" && sha256sum "$(basename "$base")") >"$base.sha256.tmp"
else
  (cd "$BACKUP_DIR" && shasum -a 256 "$(basename "$base")") >"$base.sha256.tmp"
fi
mv "$base.sha256.tmp" "$base.sha256"
chmod 600 "$base.sha256"
find "$BACKUP_DIR" -type f \( -name '*.sql.gz' -o -name '*.sql.gz.sha256' \) -mtime "+$RETENTION_DAYS" -delete
printf 'OK backup=%s bytes=%s utc=%s\n' "$(basename "$base")" "$(wc -c <"$base" | tr -d ' ')" "$stamp"
