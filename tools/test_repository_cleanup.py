#!/usr/bin/env python3
"""Focused contract for repository hygiene and complete template packaging."""

from pathlib import Path
import subprocess
import sys
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]


def tracked(*paths):
    result = subprocess.run(
        ["git", "ls-files", "--", *paths],
        cwd=str(ROOT),
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        universal_newlines=True,
    )
    assert result.returncode == 0, result.stderr
    return [line for line in result.stdout.splitlines() if line]


assert not tracked("administrator/cache"), "Generated administrator cache must not be tracked"
assert not (ROOT / "DEPLOYMENT-SECURITY.txt").exists(), "Superseded deployment summary must be removed"
assert not (ROOT / "tools" / "news-cleanup-report.json").exists(), "Generated cleanup report must be removed"
assert not (ROOT / "docs" / "superpowers" / "specs" / "2026-07-18-repository-cleanup-design.md").exists(), "One-off cleanup spec must not remain"

ignore = subprocess.run(
    ["git", "check-ignore", "-q", ".superpowers/probe"], cwd=str(ROOT)
)
assert ignore.returncode == 0, "Local .superpowers state must be ignored"


manifest_path = ROOT / "templates" / "pn_natuna_2026" / "templateDetails.xml"
manifest = ET.parse(str(manifest_path)).getroot()
files = manifest.find("files")
assert files is not None, "Template manifest files section is missing"
filenames = {node.text for node in files.findall("filename")}
folders = {node.text for node in files.findall("folder")}
required_files = {
    "hero-slider.php",
    "instagram-feed.php",
    "instansi-feed.php",
    "sipp-schedule.php",
    "stats-counter.php",
    "youtube-feed.php",
}
assert required_files <= filenames, "Template manifest omits runtime helpers: {}".format(sorted(required_files - filenames))
assert {"data", "fonts"} <= folders, "Template manifest must package data and fonts"
for filename in required_files:
    assert (manifest_path.parent / filename).is_file(), "Manifest helper does not exist: {}".format(filename)
for folder in ("data", "fonts"):
    assert (manifest_path.parent / folder).is_dir(), "Manifest folder does not exist: {}".format(folder)


print("repository cleanup contract: ok")
