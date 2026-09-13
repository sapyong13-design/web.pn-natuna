"""Run after generate-sitemap.php; checks public image discovery and safe dates."""
from pathlib import Path
from urllib.parse import urlsplit, unquote
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
ns = {'s': 'http://www.sitemaps.org/schemas/sitemap/0.9', 'i': 'http://www.google.com/schemas/sitemap-image/1.1'}
tree = ET.parse(root / 'sitemap.xml')
pages = tree.findall('s:url', ns)
home = next(p for p in pages if p.findtext('s:loc', namespaces=ns) == 'https://pn-natuna.go.id/')
assert home.find('s:lastmod', ns) is None, 'Homepage has no authoritative aggregate modification date'
assert any(n.text.endswith('/tolak-gratifikasi-popup-20260912.webp') for n in home.findall('i:image/i:loc', ns))
images = tree.findall('.//i:loc', ns)
assert images
for image in images:
    url = urlsplit(image.text)
    assert url.scheme == 'https' and url.netloc == 'pn-natuna.go.id'
    file = (root / unquote(url.path).lstrip('/')).resolve()
    assert file.is_relative_to((root / 'images').resolve()) and file.is_file(), image.text
for page in pages:
    locations = [n.text for n in page.findall('i:image/i:loc', ns)]
    assert len(locations) == len(set(locations))
print(f'OK: {len(pages)} pages, {len(images)} image entries; poster discoverable, local files exist')
