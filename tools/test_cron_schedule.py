"""Kontrak jadwal dua skrip cron: per jam vs harian.

Yang dijaga: pembagian tugas tidak boleh bocor.

- `cron-refresh-all.sh` (per jam) mengurus sumber yang angkanya ingin dilihat segera
  setelah dokumen masuk: kabar instansi, YouTube, SIPP, skor survei di beranda
  (Kinerja), donut realisasi anggaran (Akuntabilitas), dan sitemap.
- `cron-refresh-transparansi.sh` (harian) hanya mengurus daftar kartu halaman menu
  Transparansi. Sifatnya arsip dan hanya berubah saat ada dokumen baru diunggah;
  menjalankannya tiap jam berarti membaca puluhan folder Drive dan menulis ulang
  sebelas artikel tanpa perubahan.

Berkas ini memeriksa isi skrip, bukan menjalankannya - eksekusi dengan runner palsu
ada di `tools/test_cron_refresh_isolation.sh`.

Jalankan: python tools/test_cron_schedule.py
"""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
HOURLY = ROOT / "tools" / "cron-refresh-all.sh"
DAILY = ROOT / "tools" / "cron-refresh-transparansi.sh"

failures = []

def expect(condition, message):
    if not condition:
        failures.append(message)

def calls(source: str) -> list[str]:
    """Baris `run …` yang benar-benar dieksekusi (bukan komentar)."""
    return [
        line.strip()
        for line in source.splitlines()
        if re.match(r"\s*run\s+\S+", line) and not line.strip().startswith("#")
    ]

for path in (HOURLY, DAILY):
    expect(path.is_file(), f"Skrip cron hilang: {path.name}")

hourly = HOURLY.read_text(encoding="utf-8")
daily = DAILY.read_text(encoding="utf-8")
hourly_calls = calls(hourly)
daily_calls = calls(daily)

# --- per jam: enam job, tanpa halaman Transparansi -----------------------------
expect(len(hourly_calls) == 6,
       f"Cron per jam harus menjalankan 6 job, dapat {len(hourly_calls)}: {hourly_calls}")
for needle, label in (
    ("cron-refresh-instansi.php", "kabar instansi"),
    ("cron-refresh-youtube.php", "YouTube"),
    ("cron-refresh-sipp.php", "SIPP"),
    ("refresh-survey.py", "skor survei beranda"),
    ("refresh-dipa.py", "realisasi anggaran beranda"),
    ("generate-sitemap.php", "sitemap"),
):
    expect(any(needle in call for call in hourly_calls),
           f"Cron per jam harus menjalankan {label} ({needle}).")
expect(not any("refresh-surveys.py" in call for call in hourly_calls),
       "Daftar kartu Transparansi tidak boleh ikut di cron per jam.")

# --- harian: hanya halaman Transparansi ---------------------------------------
expect(len(daily_calls) == 0,
       "Skrip harian memanggil satu perintah langsung, bukan lewat helper run().")
expect("refresh-surveys.py" in daily, "Cron harian harus menjalankan refresh-surveys.py.")
expect("--write" in daily,
       "Cron harian wajib memakai --write; tanpa itu skrip hanya melaporkan rencana.")
for needle, label in (
    ("refresh-survey.py", "skor survei beranda"),
    ("refresh-dipa.py", "realisasi anggaran"),
    ("cron-refresh-instansi.php", "kabar instansi"),
    ("cron-refresh-youtube.php", "YouTube"),
    ("cron-refresh-sipp.php", "SIPP"),
    ("generate-sitemap.php", "sitemap"),
):
    expect(needle not in daily,
           f"Cron harian tidak boleh menjalankan {label}; itu tugas cron per jam.")
# Interval internal sudah tidak dipakai: jadwalnya ditentukan crontab.
expect("--min-interval-days" not in daily,
       "Interval internal tidak diperlukan lagi; jadwal ditentukan crontab.")

# --- penjaga yang sama di kedua skrip -----------------------------------------
for name, source in (("per jam", hourly), ("harian", daily)):
    expect("public_html" in source,
           f"Cron {name} harus menolak target selain public_html.")
    expect(".git" in source,
           f"Cron {name} harus menolak checkout Git (bundle rilis read-only).")
    expect("MYSQL_DEFAULTS_FILE" in source,
           f"Cron {name} harus membaca kredensial dari berkas, bukan argumen.")
    expect("PN_NATUNA_JPATH_ROOT" in source,
           f"Cron {name} harus memakai env produksi, bukan path tebakan.")

# --- berkas rilis harus memuat skrip harian -----------------------------------
archive = ROOT / "CRON-AUTOUPDATE-HANDOFF.md"
if archive.is_file():
    doc = archive.read_text(encoding="utf-8")
    expect("cron-refresh-transparansi.sh" in doc,
           "Dokumen cron harus menyebut skrip harian supaya operator memasukkannya ke crontab.")

# --- berkas rilis harus memuat kedua skrip dan semua dependensinya -------------
# Bundle cron produksi dibuat dengan `git archive` berisi daftar berkas eksplisit.
# Skrip yang lupa didaftarkan tidak pernah ikut, dan cron harian gagal di server.
archive_cmd = ""
for line in archive.read_text(encoding="utf-8").splitlines() if archive.is_file() else []:
    if "git -C" in line and "archive" in line:
        archive_cmd = line
        break
for needle in (
    "tools/cron-refresh-all.sh",
    "tools/cron-refresh-transparansi.sh",
    "tools/refresh-survey.py",
    "tools/refresh-surveys.py",
    "tools/refresh-dipa.py",
    "tools/generate-sitemap.php",
    "cron-refresh-instansi.php",
):
    expect(needle in archive_cmd,
           f"Perintah git archive bundle cron harus memuat {needle}.")

if failures:
    sys.stderr.write("\n".join(failures) + "\n")
    sys.exit(1)
print("Cron schedule: OK")
