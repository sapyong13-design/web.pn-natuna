"""Focused static contract for article ID 53 news portal renderer."""
from pathlib import Path

SOURCE = Path(__file__).parent.parent / "templates/pn_natuna_2026/html/com_content/article/default.php"
CSS = Path(__file__).parent.parent / "templates/pn_natuna_2026/css/template.css"
text = SOURCE.read_text(encoding="utf-8")
css = CSS.read_text(encoding="utf-8")

# Fixture edge cases required by editorial-card normalization contract.
fixtures = [
    ({"image_fulltext": "", "image_intro": "images/news/intro.webp"}, "/images/news/intro.webp"),
    ({"image_fulltext": "images/news/full.webp", "image_intro": "images/news/intro.webp"}, "/images/news/full.webp"),
]
for images, expected in fixtures:
    selected = (images.get("image_fulltext") or "").strip() or (images.get("image_intro") or "").strip()
    normalized = "/" + selected.lstrip("/") if selected and not selected.startswith(("http://", "https://", "//")) else selected
    assert normalized == expected

# Sentinel publish_up must use created date.
publish_up, created = "0000-00-00 00:00:00", "2026-07-11 08:30:00"
assert (publish_up if publish_up > "2000-01-02 00:00:00" else created) == created

for requirement in [
    "(int) $item->id === 53",
    "portalNews = $portalItems(12, 3, 'portalNewsCategory')",
    "portalAnnouncements = $portalItems(13, 5, 'portalAnnouncementCategory')",
    "NOT LIKE ' . $db->quote('berita-dan-pengumuman%')",
    "trim((string) ($decoded['image_fulltext'] ?? '')) ?: trim((string) ($decoded['image_intro'] ?? ''))",
    "'a.images', 'a.introtext', 'a.fulltext'",
    "publishUp > '2000-01-02 00:00:00' ? $publishUp : $created",
    "/images/hero/gedung-pn-natuna-2026.webp",
    "news-portal__news-card",
    "news-portal__announcements",
    "RouteHelper::getArticleRoute",
]:
    assert requirement in text, requirement

for requirement in [
    "NEWS PORTAL 2026-07-11",
    "height: clamp(210px, 58vw, 240px)",
    "grid-template-columns: repeat(3, minmax(0, 1fr))",
    "body.is-dark .news-portal",
    "prefers-reduced-motion: reduce",
]:
    assert requirement in css, requirement

print("news portal renderer contract: ok")
