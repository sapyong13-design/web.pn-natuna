# News Deduplication and Excerpt Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove duplicate public news cards based on identical image bytes and restore excerpts for surviving imported news.

**Architecture:** A focused Python tool reads an exported JSON row set, hashes repository-local primary images, deterministically selects one winner per hash group, extracts clean first-paragraph excerpts, and emits idempotent SQL plus an audit JSON report. SQL is applied twice to local MariaDB and verified through DB queries and localhost browser QA.

**Tech Stack:** Python 3.11 standard library, Joomla 5 content schema, MariaDB 8.4, PHP localhost, Chromium.

## Global Constraints

- Duplicate losers move to `state = -2`; never hard-delete.
- Prefer non-`legacy-*`; legacy ties use publish date, meaningful body length, then lowest ID.
- Excerpts come only from first meaningful fulltext paragraph.
- Apply only to local `pn_natuna_rebuild`.
- Do not commit or push.

---

### Task 1: Cleanup engine

**Files:**
- Create: `tools/cleanup-news-import.py`
- Create: `tools/test_cleanup_news_import.py`

**Interfaces:**
- Consumes JSON rows with `id`, `alias`, `publish_up`, `introtext`, `fulltext`, and `image`.
- Produces `find_duplicate_losers(rows, root) -> list[int]`, `extract_excerpt(html) -> str`, `render_sql(losers, excerpts) -> str`.

- [ ] Write tests proving byte-identical images group despite different paths, non-legacy wins, legacy tie order is deterministic, image-only paragraphs are skipped, and SQL uses `state=-2` without `DELETE`.
- [ ] Run `python tools/test_cleanup_news_import.py`; expect contract failures before implementation.
- [ ] Implement minimal standard-library cleanup engine.
- [ ] Run test again; expect `news cleanup contract: ok`.

### Task 2: Local migration and audit

**Files:**
- Generate: `database/migrations/20260715_cleanup_duplicate_news.sql`
- Generate locally, inspect, then discard: `tools/news-cleanup-report.json` (runtime audit artifact; not tracked)

- [ ] Export active category 12 rows from local MariaDB as JSON input.
- [ ] Run cleanup tool against repository root.
- [ ] Inspect audit report: every loser belongs to a repeated image hash; every winner follows chosen rule.
- [ ] Apply generated SQL twice to `pn_natuna_rebuild`; expect identical active/trash counts after both runs.
- [ ] Query active rows and prove no repeated primary-image byte hash remains.
- [ ] Query surviving imported rows and report empty excerpts only where no meaningful body exists.

### Task 3: Local browser verification

- [ ] Load `http://127.0.0.1:8080/berita` at 1280x900 and 390x844.
- [ ] Verify duplicate examples from screenshot appear once, every visible card has a title, repaired excerpts render, and no horizontal overflow occurs.
- [ ] Open representative imported detail and retained curated detail; expect HTTP render with correct h1 and local images.
- [ ] Confirm repository remains uncommitted and unpushed.
