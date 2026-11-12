#!/bin/sh
# Sinkronisasi harian seluruh halaman menu Transparansi dari Google Drive.
#
# Dipisah dari `cron-refresh-all.sh` (per jam) karena sifat perubahannya berbeda:
# daftar laporan transparansi hanya berubah ketika ada dokumen baru diunggah ke
# Drive - hitungannya hari atau minggu, bukan jam. Menjalankannya tiap jam berarti
# membaca ~30 folder Drive dan menulis ulang sebelas artikel tanpa perubahan.
#
# Yang diurus skrip ini: Ringkasan LKjIP, Laporan Tahunan, SAKIP, Laporan Realisasi
# Anggaran, LHKPN, Laporan SKM, Laporan IPAK, Laporan Survei Harian, Laporan
# Keuangan, E-Brosur, Peraturan dan Kebijakan, Laporan Pelayanan Informasi Publik,
# dan Lelang Barang dan Jasa.
#
# Kartu beranda (Kinerja) dan widget realisasi anggaran tetap per jam di
# `cron-refresh-all.sh`.
set -eu

: "${PN_NATUNA_JPATH_ROOT:?set PN_NATUNA_JPATH_ROOT}"
PN_NATUNA_CRON_SOURCE_ROOT=$(CDPATH= cd -- "$(dirname "$0")/.." && pwd -P)
: "${PYTHON_BIN:=/usr/bin/python3}"
: "${MYSQL_BIN:=/usr/bin/mysql}"
: "${MYSQL_DEFAULTS_FILE:?set MYSQL_DEFAULTS_FILE}"
: "${DB_NAME:?set DB_NAME}"

# Sama seperti cron per jam: bundle rilis bersifat read-only, bukan checkout Git.
if [ -e "$PN_NATUNA_CRON_SOURCE_ROOT/.git" ]; then
    printf 'ERROR: cron source must be an immutable release bundle, not a Git checkout: %s\n' "$PN_NATUNA_CRON_SOURCE_ROOT" >&2
    exit 77
fi
: "${PN_NATUNA_PRIVATE_ROOT:=$(dirname "$PN_NATUNA_JPATH_ROOT")/private}"

export PN_NATUNA_JPATH_ROOT PN_NATUNA_PRIVATE_ROOT MYSQL_BIN MYSQL_DEFAULTS_FILE DB_NAME

mkdir -p "$PN_NATUNA_PRIVATE_ROOT/logs"

# Skrip ini hanya menulis daftar kartu ke artikel; tidak menyentuh kode, template,
# maupun skema database.
case "$PN_NATUNA_JPATH_ROOT" in
    */public_html) ;;
    *) printf 'ERROR: production refresh target must be public_html: %s\n' "$PN_NATUNA_JPATH_ROOT" >&2; exit 77 ;;
esac

# `--write` wajib: tanpa itu skrip hanya melaporkan rencana perubahan.
if "$PYTHON_BIN" "$PN_NATUNA_CRON_SOURCE_ROOT/tools/refresh-surveys.py" \
    --write \
    --database "$DB_NAME" \
    --defaults-file "$MYSQL_DEFAULTS_FILE"; then
    printf '%s transparansi berhasil\n' "$(date -u +%FT%TZ)"
else
    code=$?
    printf '%s transparansi gagal (exit %s)\n' "$(date -u +%FT%TZ)" "$code" >&2
    exit "$code"
fi
