# Editorial Article Override Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give every news and announcement detail an automatic official-editorial layout through a Joomla-native article override while leaving all other article categories on the installed core template.

**Architecture:** `html/com_content/article/default.php` resolves the current category ancestry. Non-news categories immediately require the installed core `com_content` article template. News/announcement categories render one shared semantic shell with channel-specific hero/document treatment, related-item query, and isolated CSS/vanilla-JS enhancements.

**Tech Stack:** Joomla 4/5 PHP templates, Joomla database query builder, semantic HTML, CSS, vanilla JavaScript, Chromium behavior QA.

## Global Constraints

- No plugin, page builder, framework, or database content migration.
- Non-news fallback must directly require `JPATH_BASE . '/components/com_content/tmpl/article/default.php'`; never clone core template.
- Detect direct and descendant categories under aliases `berita` and `pengumuman`.
- Preserve Joomla content events, pagination, article links, tags, edit controls, and publication-state notices.
- Related query: maximum three, same resolved channel/category, current item excluded, respecting access, language, publish-up, publish-down, one query.
- Mobile targets at least 44×44px; no horizontal overflow.
- Work remains local: do not commit or push.

---

### Task 1: Category Routing and Core Fallback

**Files:**
- Create: `templates/pn_natuna_2026/html/com_content/article/default.php`

- [ ] Capture current DOM signature for `/transparansi` and representative non-news route.
- [ ] Add category ancestor resolution using Joomla category table/query and aliases.
- [ ] For neither channel, require installed core article template and return.
- [ ] Render a minimal channel marker for news/announcement to prove routing before full layout.
- [ ] Verify `/transparansi` signature remains unchanged and direct news, direct announcement, and child-category articles resolve correctly.

### Task 2: Editorial News and Announcement Shell

**Files:**
- Modify: `templates/pn_natuna_2026/html/com_content/article/default.php`

- [ ] Capture failing assertions for raw English metadata labels, missing semantic channel class, missing Indonesian date/reading time, and missing hero/fallback.
- [ ] Implement publication-state notices, edit control, plugin events, pagination, tags, article links, and access/no-auth behavior using installed core interfaces.
- [ ] Implement shared article header, Indonesian date, reading-time calculation, schema type, back link, and channel-specific copy.
- [ ] Implement news hero priority `image_fulltext` then `image_intro`, escaped alt/caption, and branded fallback.
- [ ] Implement announcement institutional/document-first treatment and preserve attachments/article links.
- [ ] Verify representative news and announcement details.

### Task 3: Related Content and Publication Utilities

**Files:**
- Modify: `templates/pn_natuna_2026/html/com_content/article/default.php`

- [ ] Capture failing assertions for absent publication note and related section.
- [ ] Add one related query selecting up to three eligible articles, excluding current item, respecting access/language/publication dates.
- [ ] Render image/fallback, Indonesian date, title, and routed URL; hide empty section.
- [ ] Add publication note and share/copy/WhatsApp controls with an aria-live status target.
- [ ] Verify query exclusion and limit from rendered output.

### Task 4: Editorial Visual System

**Files:**
- Modify: `templates/pn_natuna_2026/css/template.css`

- [ ] Capture failing geometry/typography assertions at 320, 390, 760, 761, 1280, and 1440.
- [ ] Add isolated `.editorial-article-*` styles: header, metadata, hero/fallback, 68–72ch body, lead paragraph, headings, figures, tables, blockquotes, utilities, related grid.
- [ ] Add explicit announcement, mobile, dark-mode, focus-visible, and reduced-motion variants.
- [ ] Contain long URLs/tables/images without widening page.
- [ ] Verify all geometry, target-size, contrast-state, and overflow assertions.

### Task 5: Share Enhancement and End-to-End QA

**Files:**
- Modify: `templates/pn_natuna_2026/js/template.js`
- Update: `HANDOFF.md`

- [ ] Capture failing browser assertions for share/copy behavior.
- [ ] Add `setupEditorialArticleShare()` using `navigator.share` when available and Clipboard API/fallback for copy, updating the aria-live status.
- [ ] Run representative route matrix in light/dark/reduced-motion and viewport matrix.
- [ ] Confirm `/transparansi` remains core fallback, listings remain unchanged, navigation/footer remain in bounds, plugin events remain present, and no console errors occur.
- [ ] Update HANDOFF with editor contract, override scope, core fallback rule, and maintenance notes.
- [ ] Request independent review; fix valid findings and rerun affected checks.
- [ ] Leave all changes uncommitted and unpushed.
