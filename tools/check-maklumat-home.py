from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
MYSQL = Path(r"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe")
IMAGE = ROOT / "images" / "layanan" / "maklumat-pelayanan-2026.png"
CSS = ROOT / "templates" / "pn_natuna_2026" / "css" / "template.css"
EXPECTED_URL = "/images/layanan/maklumat-pelayanan-2026.png"


def fail(message: str) -> None:
    print(f"FAIL: {message}", file=sys.stderr)
    raise SystemExit(1)


if not IMAGE.is_file() or IMAGE.stat().st_size == 0:
    fail(f"missing image {IMAGE}")

css = CSS.read_text(encoding="utf-8")
if ".maklumat-duo .maklumat-doc" not in css or "width: 70%;" not in css:
    fail("homepage maklumat CSS is not scoped to 70% width")

query = "SELECT content FROM pnn_modules WHERE id=808"
content = subprocess.check_output(
    [str(MYSQL), "-h127.0.0.1", "-uroot", "--batch", "--skip-column-names", "pn_natuna_rebuild", "-e", query],
    cwd=ROOT,
    text=True,
    encoding="utf-8",
)

if EXPECTED_URL not in content:
    fail("module 808 does not use new Maklumat Pelayanan PNG")

if "/images/layanan/maklumat-pelayanan-2026.jpg" in content:
    fail("module 808 still references old Maklumat Pelayanan JPG")
if "JOKO CIPTANTO" not in content.upper() or "13 April 2026" not in content:
    fail("module 808 text does not match new Maklumat Pelayanan certificate")


print("OK: maklumat homepage image and 70% width configured")
