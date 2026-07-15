# Live Content Import and Transparency Navigation Design

## Goal
Import every publicly available PN Natuna news and announcement article into the rebuild with local images, then make Transparency navigation use the same visual language as other internal page families.

## Content source
- Canonical host: `https://www.pn-natuna.go.id`.
- News listing currently exposes 9 pages at `/index.php/en/berita/berita-terkini?start=N`, 10 items per page except the final page.
- Announcement endpoint must be discovered from live navigation/category links; guessed or 404 routes are rejected.
- RSS is not authoritative because its parsed titles are empty. Importer uses listing and detail HTML.

## Import architecture
A Python standard-library importer performs deterministic crawl and export:

1. Discover canonical news and announcement listing URLs from the live site.
2. Traverse pagination until no unseen detail URLs remain.
3. Accept only HTTPS URLs on `www.pn-natuna.go.id`.
4. Parse title, publication date, article body, category, detail URL, and public images.
5. Remove scripts, iframes, event handlers, template chrome, navigation, accessibility widgets, and remote embeds.
6. Download valid JPEG/PNG/WebP images within configured size limits to `images/news/imported/` using content hashes or deterministic slugs.
7. Rewrite article image references to local root-relative paths.
8. Produce a reviewed JSON manifest carrying provenance, normalized content, source URL, category, alias, dates, and image paths.
9. Upsert into local `pn_natuna_rebuild` categories 12 (Berita) and 13 (Pengumuman) by immutable source URL stored in article metadata; reruns update instead of duplicate.
10. Generate an immutable MariaDB-compatible migration or import artifact so the result is reproducible from Git.

Direct writes to staging are prohibited because `--full-staging` resets staging from the private canonical dump.

## Content safety
- No authenticated endpoints, cookies, sessions, admin pages, or edit URLs.
- No source scripts, style blocks, inline event handlers, iframes, objects, forms, or unknown schemes.
- Images require a permitted MIME signature and bounded response size.
- Original public URL is retained as provenance, not as a hotlink dependency.
- Imported articles use Joomla aliases that remain stable across reruns.
- Existing local articles with no matching provenance remain untouched.

## Transparency navigation
The current desktop dropdown and separate mobile disclosure navigation are replaced by one semantic grouped grid matching internal navigation patterns such as `.profile-unit-nav`.

- Four group headings remain: Akuntabilitas Kinerja, Keuangan, Survei dan Integritas, Informasi Publik.
- Each group contains plain navigation links with 44 px minimum targets.
- Desktop uses four group columns with consistent border, radius, spacing, typography, and active maroon fill.
- Tablet uses two columns; mobile uses one column for long labels.
- The same markup works on all breakpoints; no duplicate desktop/mobile navigation trees.
- All 13 current routes and `aria-current="page"` remain unchanged.
- Landing gateway content remains, but navigation styling no longer resembles unrelated dropdown cards.
- Dark mode, keyboard focus, reduced motion, and long-label wrapping remain supported.

## Database and deployment

```text
crawl live public content
→ review deterministic manifest/images
→ upsert local rebuild DB
→ run focused contracts and browser QA
→ export fresh MariaDB-compatible full staging dump
→ upload it as ~/private/pn-natuna-db/current.sql.gz
→ push importer, manifest, images, migration, renderer, CSS, tests
→ run python3 tools/deploy-cpanel.py --full-staging
```

The private dump is never committed. Git contains only reproducible public source data/migrations and downloaded public images.

## Acceptance criteria
- Every discovered pagination page is visited exactly once.
- Every unique public detail URL maps to one local article.
- News and announcement counts are reported separately.
- All accepted images are local and readable; no article hotlinks old-site images.
- Sanitized content contains no executable/embedded legacy markup.
- Imported articles render through existing news portal/category overrides.
- Transparency emits one grouped navigation tree, not desktop/mobile duplicates.
- All Transparency routes return HTTP 200 with correct active state.
- No horizontal overflow at 320, 390, 760, 761, 1280, and 1440 px.
- Fresh MariaDB dump contains imported content and remains compatible with staging full reset.