#!/usr/bin/env python3
"""Render poster Instagram DIPA: PNG tiap slide, MP4 Reels, dan galeri.

Satu perintah untuk seluruh rantai, karena tiap langkah punya jebakan sendiri:

1. `tools/make-dipa-poster.php` menulis HTML dari data modul 816.
2. Chrome headless memotret HTML jadi PNG **tepat 1080x1350**. Tangkapan lewat
   otomasi biasa keluar 1350x1688 karena jendela browser tidak mengikuti viewport
   emulasi; `--window-size` + `--force-device-scale-factor` tidak punya masalah itu.
3. Animasi dirender per frame dengan parameter `?frame=1` (tanpa requestAnimationFrame)
   lalu digabung ffmpeg. Frame deterministik, jadi hasilnya sama tiap kali.
4. Galeri `index.html` ditulis ulang supaya tombol unduh menunjuk berkas yang ada.

Pemakaian:
    python tools/render-social.py                    # semua periode
    python tools/render-social.py --period=2026-09   # satu periode
    python tools/render-social.py --skip-video       # PNG saja, lebih cepat

Prasyarat: PHP di PATH, Chrome/Edge terpasang, ffmpeg (atau `imageio-ffmpeg`),
dan `pymupdf` untuk slide rincian.
"""
from __future__ import annotations

import argparse
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "tmp" / "social"
FPS = 24
DURATION = 8
WIDTH, HEIGHT = 1080, 1350
CHROME_CANDIDATES = [
    r"C:\Program Files\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
    r"C:\Program Files\Microsoft\Edge\Application\msedge.exe",
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
    "/usr/bin/google-chrome",
    "/usr/bin/chromium",
]


def find_chrome() -> str:
    for candidate in CHROME_CANDIDATES:
        if Path(candidate).is_file():
            return candidate
    found = shutil.which("chrome") or shutil.which("google-chrome") or shutil.which("chromium")
    if found:
        return found
    raise SystemExit("Chrome/Edge tidak ditemukan; poster tidak bisa dipotret.")


def find_ffmpeg() -> str:
    found = shutil.which("ffmpeg")
    if found:
        return found
    try:
        import imageio_ffmpeg  # type: ignore

        return imageio_ffmpeg.get_ffmpeg_exe()
    except ImportError:
        raise SystemExit("ffmpeg tidak ditemukan; pasang ffmpeg atau `pip install imageio-ffmpeg`.")


def php_binary() -> str:
    """PHP dari PATH, atau lewat variabel PHP_BIN untuk instalasi portabel."""
    import os

    explicit = os.environ.get("PHP_BIN")
    if explicit and Path(explicit).is_file():
        return explicit
    for name in ("php", "php.exe"):
        found = shutil.which(name)
        if found:
            return found
    raise SystemExit("PHP tidak ditemukan; set PHP_BIN ke php.exe.")


def run(command: list[str], **kwargs) -> subprocess.CompletedProcess:
    result = subprocess.run(command, capture_output=True, text=True, encoding="utf-8", errors="replace", **kwargs)
    if result.returncode != 0:
        raise SystemExit(f"Gagal: {' '.join(command[:3])}...\n{result.stderr[-800:]}")
    return result


def shoot(chrome: str, url: str, destination: Path, scale: int = 1) -> None:
    run([
        chrome, "--headless=new", "--disable-gpu", "--hide-scrollbars",
        "--no-first-run", "--no-default-browser-check", "--disable-extensions",
        "--virtual-time-budget=4000",
        f"--force-device-scale-factor={scale}",
        f"--window-size={WIDTH},{HEIGHT}",
        f"--screenshot={destination}",
        url,
    ])


def render_video(chrome: str, ffmpeg: str, slug: str) -> Path | None:
    """Frame deterministik -> MP4. Frame disimpan di folder sementara lalu dihapus."""
    video_html = OUT / f"dipa-ig-{slug}-video.html"
    if not video_html.is_file():
        return None
    frames = OUT / "frames" / slug
    if frames.exists():
        shutil.rmtree(frames)
    frames.mkdir(parents=True)

    # Satu proses Chrome per frame: 192 proses terlalu mahal. Sebagai gantinya,
    # halaman yang sama dimuat sekali dan setiap frame ditangkap dengan
    # `?frame=1&t=<detik>` lewat mode rekam deterministik.
    total = FPS * DURATION
    for index in range(total):
        moment = index / FPS
        shoot(chrome, f"http://localhost:8080/tmp/social/dipa-ig-{slug}-video.html?frame=1&t={moment:.4f}",
              frames / f"f_{index:04d}.png")
        if index % 24 == 0:
            print(f"    frame {index + 1}/{total}", flush=True)

    target = OUT / f"dipa-ig-{slug}.mp4"
    run([
        ffmpeg, "-y", "-loglevel", "error", "-framerate", str(FPS),
        "-i", str(frames / "f_%04d.png"),
        "-c:v", "libx264", "-preset", "medium", "-crf", "21",
        "-pix_fmt", "yuv420p", "-movflags", "+faststart", str(target),
    ])
    shutil.rmtree(frames)
    return target


def periods(php: str) -> list[str]:
    result = run([php, str(ROOT / "tools" / "make-dipa-poster.php"), "--list"])
    return [line.strip() for line in result.stdout.splitlines() if line.strip()]


def slugify(label: str) -> str:
    return "".join(character if character.isalnum() else "-" for character in label).strip("-")


def resolve_slugs(output: str, requested: str | None) -> list[str]:
    """Slug dari keluaran skrip PHP (`SLUG=...`), bukan ditebak dari argumen.

    `--period=2026-09` diterima PHP dalam banyak bentuk (2026-9, "September", 9) dan
    slug akhirnya selalu "September-2026"; menebak sendiri membuat pencarian berkas
    video gagal dan render dilewati tanpa error.
    """
    slugs = [line.split("=", 1)[1].strip() for line in output.splitlines() if line.startswith("SLUG=")]
    if requested and slugs:
        return slugs[:1]
    return slugs


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--period", help="satu periode, mis. 2026-09 atau 'Juli 2026'")
    parser.add_argument("--skip-video", action="store_true", help="lewati render MP4")
    parser.add_argument("--skip-png", action="store_true", help="lewati tangkapan PNG")
    parser.add_argument("--python", default=sys.executable, help="interpreter dengan pymupdf")
    args = parser.parse_args()

    php = php_binary()
    chrome = find_chrome()
    ffmpeg = None if args.skip_video else find_ffmpeg()
    OUT.mkdir(parents=True, exist_ok=True)

    build = [php, str(ROOT / "tools" / "make-dipa-poster.php"), f"--python={args.python}"]
    if args.period:
        build.append(f"--period={args.period}")
    else:
        build.append("--all")
    print("1/3 menyusun HTML dari data modul...", flush=True)
    output = run(build).stdout

    slugs = resolve_slugs(output, args.period) or [slugify(label) for label in periods(php)]
    for slug in slugs:
        if not args.skip_png:
            print(f"2/3 memotret slide {slug}...", flush=True)
            for suffix in ("", "-rincian"):
                html = OUT / f"dipa-ig-{slug}{suffix}.html"
                if html.is_file():
                    shoot(chrome, f"http://localhost:8080/tmp/social/{html.name}", OUT / f"dipa-ig-{slug}{suffix}.png")
        if ffmpeg:
            print(f"3/3 merender video {slug}...", flush=True)
            render_video(chrome, ffmpeg, slug)

    # Galeri ditulis ulang oleh skrip PHP pada mode --all; pada mode satu periode
    # tombolnya tetap menunjuk berkas yang baru dibuat.
    if args.period:
        run([php, str(ROOT / "tools" / "make-dipa-poster.php"), "--all", f"--python={args.python}"])

    missing = [path.name for path in OUT.glob("dipa-ig-*.png") if path.stat().st_size == 0]
    if missing:
        raise SystemExit(f"Berkas kosong: {', '.join(missing)}")
    print(f"\nSelesai. Galeri: {OUT / 'index.html'}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
