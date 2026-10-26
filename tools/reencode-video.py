#!/usr/bin/env python3
"""Encode ulang video poster jadwal sidang dengan ffmpeg.

MediaRecorder di browser menghasilkan MP4 yang cepat tapi tidak efisien:
bitrate berubah-ubah, moov di akhir file, dan profil H.264 baseline. Skrip ini
mengubahnya jadi H.264 High, CRF 18, yuv420p, faststart — lebih tajam untuk
teks kecil dan langsung bisa diputar/di-upload.

Pemakaian:
    python tools/reencode-video.py IN.mp4 [OUT.mp4]

Butuh ffmpeg di PATH (Hermes menyertakannya di tools/ffmpeg-*).
"""

from __future__ import annotations

import shutil
import subprocess
import sys
from pathlib import Path


def find_ffmpeg() -> str:
    found = shutil.which("ffmpeg")
    if found:
        return found
    bundled = sorted(
        Path.home().glob("AppData/Local/hermes/tools/ffmpeg-*/bin/ffmpeg.exe")
    )
    if bundled:
        return str(bundled[-1])
    raise SystemExit("ffmpeg tidak ditemukan. Pasang ffmpeg atau tambahkan ke PATH.")


def main(argv: list[str]) -> int:
    if len(argv) < 2:
        print(__doc__)
        return 2

    source = Path(argv[1]).expanduser()
    if not source.is_file():
        raise SystemExit(f"Berkas tidak ditemukan: {source}")
    target = Path(argv[2]).expanduser() if len(argv) > 2 else source.with_name(f"{source.stem}-hq.mp4")

    command = [
        find_ffmpeg(),
        "-y",
        "-i", str(source),
        "-c:v", "libx264",
        "-profile:v", "high",
        "-crf", "18",
        "-preset", "slow",
        "-pix_fmt", "yuv420p",
        "-r", "30",
        "-movflags", "+faststart",
        "-an",
        str(target),
    ]
    print("menjalankan:", " ".join(command))
    result = subprocess.run(command, capture_output=True, text=True)
    if result.returncode != 0:
        sys.stderr.write(result.stderr[-2000:])
        raise SystemExit(f"ffmpeg gagal (exit {result.returncode})")

    before = source.stat().st_size / 1e6
    after = target.stat().st_size / 1e6
    print(f"selesai: {target} ({before:.1f} MB -> {after:.1f} MB, CRF 18, faststart)")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
