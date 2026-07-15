"""Focused source contract for dark-mode content surfaces."""
from pathlib import Path

css = (Path(__file__).resolve().parents[1] / "templates/pn_natuna_2026/css/template.css").read_text(encoding="utf-8")
required = {
    "shared content headings": "body.is-dark .content-primary :is(h1, h2, h3, h4)",
    "shared interactive accents": "body.is-dark .content-primary :is(a, .svc-more)",
    "shared dark surface token": "--dark-content-surface: #19232b",
    "warm dark surface token": "--dark-content-raised: #222d35",
    "profile detail surfaces": "body.is-dark :is(.sejarah-page, .tupoksi-page, .visimisi-page, .struktur-page)",
    "tupoksi light panels": "body.is-dark :is(.tupoksi-hero-card, .tupoksi-process article, .tupoksi-panel",
    "warm labels": "body.is-dark :is(.tupoksi-kicker, .tupoksi-panel-label, .tupoksi-process > article > span",
    "dark figure captions": "body.is-dark :is(.tupoksi-illustration figcaption, .visimisi-illustration figcaption)",
    "structure hero gradient": "body.is-dark .struktur-hero-card",
    "structure chart heading": "body.is-dark .struktur-chart-heading",
    "structure chart canvas": "body.is-dark .struktur-chart-link",
    "quick service text": "body.is-dark .app-links .app-info strong",
    "quick service cards": "body.is-dark .app-links a",
    "news tabs": "body.is-dark .hero-news-panel .hero-tabs button",
    "news list items": "body.is-dark .hero-news-panel .hero-tab-list a",
    "profile fact gradients": "body.is-dark .sejarah-fact-grid > div",
    "mission card gradients": "body.is-dark .visimisi-mission-grid article",
    "AMPUH dark accents": "body.is-dark .ampuh-directory",
}
missing = [name for name, needle in required.items() if needle not in css]
assert not missing, "Missing dark-mode contracts: " + ", ".join(missing)
print("Dark-mode CSS contract: ok")
