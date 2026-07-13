from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
MYSQL = Path(r"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe")
IMAGE = ROOT / "images" / "layanan" / "maklumat-pelayanan-2026.webp"
CSS = ROOT / "templates" / "pn_natuna_2026" / "css" / "template.css"
EXPECTED_URL = "/images/layanan/maklumat-pelayanan-2026.webp"


def fail(message: str) -> None:
    print(f"FAIL: {message}", file=sys.stderr)
    raise SystemExit(1)


if not IMAGE.is_file() or IMAGE.stat().st_size == 0:
    fail(f"missing image {IMAGE}")

css = CSS.read_text(encoding="utf-8")
if ".maklumat-compact-docs" not in css or "grid-template-columns: repeat(2, minmax(0, 1fr));" not in css:
    fail("homepage Maklumat CSS must render two document columns")

query = "SELECT content, showtitle FROM pnn_modules WHERE id=808"
content = subprocess.check_output(
    [str(MYSQL), "-h127.0.0.1", "-uroot", "--batch", "--skip-column-names", "pn_natuna_rebuild", "-e", query],
    cwd=ROOT,
    text=True,
    encoding="utf-8",
)
content, separator, showtitle = content.rstrip("\r\n").rpartition("\t")
if not separator:
    fail("module 808 query returned an invalid row")

if EXPECTED_URL not in content:
    fail("module 808 does not use current Maklumat Pelayanan WebP")
if "maklumat-pelayanan-2026.jpg" in content or "maklumat-pelayanan-2026.png" in content:
    fail("module 808 still references removed Maklumat Pelayanan image")
if content.count('class="maklumat-compact-doc"') != 2:
    fail("module 808 must contain two Maklumat documents")
if "maklumat-compact-intro" in content or "<h2" in content:
    fail("module 808 must not duplicate its Joomla title")
if showtitle != "1":
    fail("module 808 Joomla title must be enabled")


print("OK: Maklumat homepage has one Joomla title and two current document cards")
