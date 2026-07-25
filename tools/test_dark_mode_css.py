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
    "quick service outer surface": "body.is-dark .quick-links.app-strip",
    "unified dark topbar": "body.is-dark .topbar {\n  background: #151c22;",
    "dark brand header": "body.is-dark .header-brand",
    "dark brand title": "body.is-dark .brand-lockup .brand-title",
    "dark brand address": "body.is-dark .brand-lockup p",

    # Panel berita hero kembali ke slider tiga slide, jadi kontrak dark mode-nya
    # ikut kembali ke selektor panel itu.
    "news tabs": "body.is-dark .hero-news-panel .hero-tabs button",
    "news list items": "body.is-dark .hero-news-panel .hero-tab-list a",
    "profile fact gradients": "body.is-dark .sejarah-fact-grid > div",
    "mission card gradients": "body.is-dark .visimisi-mission-grid article",
    "AMPUH dark accents": "body.is-dark .ampuh-directory",
    "mobile dark surface tokens": "body.is-dark {\n    --mobile-surface: #0f151a;",
    "mobile dark header title": "body.is-dark .brand-lockup .brand-title::before",
    "mobile dark menu control": "body.is-dark .menu-toggle",
    "mobile start heading": "body.is-dark.is-home .mobile-section-heading",
    "dark active video item": "body.is-dark .youtube-showcase-item.is-active",
    "dark map kicker": "body.is-dark .home-map-heading p",

}
missing = [name for name, needle in required.items() if needle not in css]
assert not missing, "Missing dark-mode contracts: " + ", ".join(missing)
assert "body.is-dark .quick-links.app-strip {\n  background: #0f151a;" in css, "Quick service wrapper must merge with the dark page surface"
assert "body.is-dark .header-brand {\n  background:" in css and "#151c22" in css, "Brand header must join the dark header surface"
assert "--mobile-surface-raised: #151c22;" in css and "--mobile-line: #46515a;" in css, "Mobile dark token family must be complete"
assert 'body.is-dark .youtube-showcase-item.is-active,\nbody.is-dark .youtube-showcase-item[aria-current="true"] {\n  border-color: var(--dark-content-accent);\n  background-color: var(--showcase-active-bg);' in css, "Active video item must apply the dark surface token above the dark base rule"

print("Dark-mode CSS contract: ok")
