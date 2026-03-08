# AGENTS.md

Guidance for AI coding agents working on `clis.dev`.

## Project Overview

`clis.dev` is a plain PHP + SQLite directory of CLI tools, organized by service/workflow and tagged for agent usefulness.

## Architecture

```text
php/
├── index.php            # Front controller, headers, route dispatch
├── lib/
│   ├── runtime.php      # Runtime module loader
│   ├── runtime/
│   │   ├── db.php       # DB helpers, search helpers, shared utilities
│   │   └── maintenance.php # Explicit schema/index maintenance
│   ├── view.php         # Shared HTML shell, icons, layout helpers
│   ├── pages.php        # Page module loader
│   ├── pages/
│   │   ├── home.php
│   │   ├── category.php
│   │   ├── cli.php
│   │   ├── search.php
│   │   ├── submit.php
│   │   └── why.php
│   ├── api.php          # API module loader
│   ├── api/
│   │   ├── public.php   # Public JSON APIs
│   │   ├── feeds.php    # rss/sitemap/llms endpoints
│   │   └── admin.php    # Authenticated admin CRUD API
├── scripts/
│   └── apply-runtime.php # Explicit runtime/schema/index maintenance
├── assets/              # Vendored JS, images, brand icons
├── favicon.svg
├── og-image.png
└── og.svg

data/
└── clis.sqlite          # Runtime SQLite database (gitignored, created locally)

scripts/
└── sync-stars.php

skills/
└── clis-search/
    └── SKILL.md
```

## Stack

- PHP 8.x
- SQLite in WAL mode
- Vendored frontend assets served locally
- Inline SVG icons

## Design Principles

- Plain PHP, no framework
- Small files split by responsibility
- Service-first discovery, not language-first
- Public API and `llms.txt` for agent use

## Routes

| Route | Description |
|-------|-------------|
| `/` | Homepage |
| `/?cat=SLUG` | Category filter |
| `/?agent` | Agent-compatible filter |
| `/?official=1` | Official CLI filter |
| `/category/{slug}` | Category page |
| `/cli/{slug}` | CLI detail page |
| `/search?q=` | Search |
| `/why` | Essay |
| `/submit` | Submission page |
| `/api/clis` | JSON API |
| `/api/search?q=` | Search API |
| `GET /api/admin/clis/{slug}` | Authenticated read for one CLI |
| `POST /api/admin/clis` | Authenticated create |
| `PATCH /api/admin/clis/{slug}` | Authenticated update |
| `DELETE /api/admin/clis/{slug}` | Authenticated delete |
| `/llms.txt` | Summary index |
| `/llms-full.txt` | Full text index |
| `/rss.xml` | Feed |

## Database Notes

Main `clis` fields include:

- `slug`, `name`, `description`, `category_slug`
- `install_cmd`, `github_url`, `website_url`, `source_url`
- `stars`, `language`, `vendor_name`, `source_type`
- `has_mcp`, `has_skill`, `has_json`, `is_official`
- `tags`, `aliases`, `brand_icon`, `ranking_score`

## Development

```bash
php/scripts/apply-runtime.php
cd php
php -S localhost:4322 index.php
```

## Data Workflow

Preferred flow:

1. curate or import data into the local SQLite DB
2. validate locally
3. run `php/scripts/apply-runtime.php` if schema/runtime changed
4. export or deploy data separately if needed

## Code Style

- `page_*` functions render HTML pages
- `api_*` functions return machine-readable endpoints
- `html_wrap()` owns the page shell and metadata
- `query()`, `query_row()`, `query_val()` handle DB reads
- `safe_external_url()` guards outbound links
- keep user-facing content factual and concise

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `CLIS_DB_PATH` | Override SQLite path | `../data/clis.sqlite` |
| `CLIS_ANALYTICS_SALT` | Salt for anonymous analytics hashes | unset |
| `GITHUB_TOKEN` | GitHub token for maintenance scripts | unset |
| `CLIS_ADMIN_API_KEY` | Bearer token for authenticated admin CRUD API | unset |
