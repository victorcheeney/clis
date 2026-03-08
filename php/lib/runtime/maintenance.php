<?php

function runtime_schema_version(): ?string {
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS app_meta (\n            key TEXT PRIMARY KEY,\n            value TEXT NOT NULL\n        )");
        return query_val("SELECT value FROM app_meta WHERE key = ?", ['runtime_schema_version']);
    } catch (Throwable $e) {
        error_log('clis.dev runtime version check failed: ' . $e->getMessage());
        return null;
    }
}
function runtime_schema_is_current(): bool {
    return runtime_schema_version() === RUNTIME_SCHEMA_VERSION;
}
function ensure_runtime_schema(): void {
    if (runtime_schema_is_current()) {
        return;
    }
    error_log('clis.dev runtime schema is stale; run scripts/apply-runtime.php');
}
function apply_runtime_schema(): void {
    $db = db();
    $db->exec("CREATE TABLE IF NOT EXISTS app_meta (\n            key TEXT PRIMARY KEY,\n            value TEXT NOT NULL\n        )");
    $db->exec("CREATE TABLE IF NOT EXISTS categories (\n            id INTEGER PRIMARY KEY AUTOINCREMENT,\n            slug TEXT UNIQUE NOT NULL,\n            name TEXT NOT NULL,\n            description TEXT,\n            sort_order INTEGER DEFAULT 0,\n            page_title TEXT,\n            meta_description TEXT,\n            intro TEXT\n        )");
    $db->exec("CREATE TABLE IF NOT EXISTS clis (\n            id INTEGER PRIMARY KEY AUTOINCREMENT,\n            slug TEXT UNIQUE NOT NULL,\n            name TEXT NOT NULL,\n            description TEXT NOT NULL,\n            category_slug TEXT NOT NULL,\n            install_cmd TEXT,\n            github_url TEXT,\n            website_url TEXT,\n            stars INTEGER DEFAULT 0,\n            language TEXT,\n            last_updated TEXT,\n            has_mcp INTEGER DEFAULT 0,\n            has_skill INTEGER DEFAULT 0,\n            has_json INTEGER DEFAULT 0,\n            is_featured INTEGER DEFAULT 0,\n            tags TEXT,\n            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n            brand_icon TEXT,\n            launched_at DATE,\n            is_official INTEGER DEFAULT 0,\n            source_type TEXT DEFAULT 'github',\n            source_url TEXT,\n            vendor_name TEXT,\n            ranking_score INTEGER DEFAULT 0,\n            aliases TEXT DEFAULT '',\n            long_description TEXT,\n            is_tui INTEGER DEFAULT 0,\n            FOREIGN KEY (category_slug) REFERENCES categories(slug)\n        )");
    $columns = [];
    $result = $db->query("PRAGMA table_info(clis)");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[$row['name']] = true;
    }
    $required = [
        'is_official' => "ALTER TABLE clis ADD COLUMN is_official INTEGER DEFAULT 0",
        'source_type' => "ALTER TABLE clis ADD COLUMN source_type TEXT DEFAULT 'github'",
        'source_url' => "ALTER TABLE clis ADD COLUMN source_url TEXT",
        'vendor_name' => "ALTER TABLE clis ADD COLUMN vendor_name TEXT",
        'ranking_score' => "ALTER TABLE clis ADD COLUMN ranking_score INTEGER DEFAULT 0",
        'aliases' => "ALTER TABLE clis ADD COLUMN aliases TEXT DEFAULT ''",
        'long_description' => "ALTER TABLE clis ADD COLUMN long_description TEXT",
        'is_tui' => "ALTER TABLE clis ADD COLUMN is_tui INTEGER DEFAULT 0",
    ];
    foreach ($required as $column => $sql) {
        if (!isset($columns[$column])) {
            $db->exec($sql);
        }
    }
    $categoryColumns = [];
    $categoryResult = $db->query("PRAGMA table_info(categories)");
    while ($categoryRow = $categoryResult->fetchArray(SQLITE3_ASSOC)) {
        $categoryColumns[$categoryRow['name']] = true;
    }
    $requiredCategoryColumns = [
        'page_title' => "ALTER TABLE categories ADD COLUMN page_title TEXT",
        'meta_description' => "ALTER TABLE categories ADD COLUMN meta_description TEXT",
        'intro' => "ALTER TABLE categories ADD COLUMN intro TEXT",
    ];
    foreach ($requiredCategoryColumns as $column => $sql) {
        if (!isset($categoryColumns[$column])) {
            $db->exec($sql);
        }
    }
    $db->exec("CREATE INDEX IF NOT EXISTS idx_clis_category ON clis(category_slug)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_clis_featured ON clis(is_featured)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_clis_official ON clis(is_official)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_clis_stars ON clis(stars DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_clis_rank ON clis(ranking_score DESC, stars DESC)");
    ensure_runtime_categories($db);
    backfill_official_clis($db);
    seed_vendor_clis($db);
    seed_agent_harnesses($db);
    seed_curated_clis($db);
    backfill_cli_aliases($db);
    normalize_local_brand_icons($db);
    refresh_search_index($db);
    $stmt = $db->prepare("INSERT INTO app_meta (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    $stmt->bindValue(1, 'runtime_schema_version', SQLITE3_TEXT);
    $stmt->bindValue(2, RUNTIME_SCHEMA_VERSION, SQLITE3_TEXT);
    $stmt->execute();
}
function ensure_runtime_categories(SQLite3 $db): void {
    $categories = [
        [
            'slug' => 'ai-agents',
            'name' => 'AI & LLM Tools',
            'description' => 'AI tools, LLM interfaces, and agent utilities.',
            'sort_order' => 1,
        ],
        [
            'slug' => 'agent-harnesses',
            'name' => 'Agent Harnesses',
            'description' => 'Terminal-native coding agents and harnesses that orchestrate work across tools.',
            'sort_order' => 1,
        ],
        [
            'slug' => 'file-management',
            'name' => 'Files & Navigation',
            'description' => 'Better ls, cat, find, grep, file managers, and navigation.',
            'sort_order' => 2,
        ],
        [
            'slug' => 'github',
            'name' => 'GitHub & Git',
            'description' => 'GitHub, Git, and version control tools.',
            'sort_order' => 3,
        ],
        [
            'slug' => 'containers',
            'name' => 'Containers & K8s',
            'description' => 'Docker, Kubernetes, and container management.',
            'sort_order' => 4,
        ],
        [
            'slug' => 'shell-utilities',
            'name' => 'Shell Utilities',
            'description' => 'Prompts, cheatsheets, autocorrect, and productivity.',
            'sort_order' => 5,
        ],
        [
            'slug' => 'system-monitoring',
            'name' => 'System Monitoring',
            'description' => 'CPU, memory, disk, network, and process monitoring.',
            'sort_order' => 6,
        ],
        [
            'slug' => 'http-apis',
            'name' => 'HTTP & APIs',
            'description' => 'HTTP clients, API testing, and request tools.',
            'sort_order' => 7,
        ],
        [
            'slug' => 'databases',
            'name' => 'Databases',
            'description' => 'PostgreSQL, MySQL, SQLite, and universal SQL clients.',
            'sort_order' => 8,
        ],
        [
            'slug' => 'data-processing',
            'name' => 'Data Processing',
            'description' => 'JSON, YAML, CSV processing and manipulation.',
            'sort_order' => 9,
        ],
        [
            'slug' => 'dev-tools',
            'name' => 'Dev Tools',
            'description' => 'Markdown, recordings, task runners, and developer utilities.',
            'sort_order' => 10,
        ],
        [
            'slug' => 'google-workspace',
            'name' => 'Google Workspace',
            'description' => 'Gmail, Drive, Calendar, Sheets, and Docs.',
            'sort_order' => 11,
        ],
        [
            'slug' => 'cloud',
            'name' => 'Cloud & Storage',
            'description' => 'AWS, GCP, cloud sync, and storage management.',
            'sort_order' => 12,
        ],
        [
            'slug' => 'utilities',
            'name' => 'Utilities',
            'description' => 'Weather, downloads, and general-purpose tools.',
            'sort_order' => 13,
        ],
        [
            'slug' => 'networking',
            'name' => 'Networking',
            'description' => 'Network tools, DNS, SSH, VPN.',
            'sort_order' => 13,
        ],
        [
            'slug' => 'security',
            'name' => 'Security',
            'description' => 'Security scanning, secrets, encryption.',
            'sort_order' => 14,
        ],
        [
            'slug' => 'media',
            'name' => 'Media & Video',
            'description' => 'Video, audio, image processing.',
            'sort_order' => 15,
        ],
        [
            'slug' => 'trading-crypto',
            'name' => 'Trading & Crypto',
            'description' => 'Crypto exchanges, market data, trading, and portfolio workflows from the terminal.',
            'sort_order' => 16,
        ],
        [
            'slug' => 'package-managers',
            'name' => 'Package Managers',
            'description' => 'Package and dependency management.',
            'sort_order' => 19,
        ],
        [
            'slug' => 'testing',
            'name' => 'Testing & QA',
            'description' => 'Testing frameworks and tools.',
            'sort_order' => 20,
        ],
    ];
    $stmt = $db->prepare("INSERT INTO categories (slug, name, description, sort_order, page_title, meta_description, intro)
                          VALUES (:slug, :name, :description, :sort_order, :page_title, :meta_description, :intro)
                          ON CONFLICT(slug) DO UPDATE SET
                            name = excluded.name,
                            sort_order = excluded.sort_order,
                            description = COALESCE(NULLIF(categories.description, ''), excluded.description),
                            page_title = COALESCE(NULLIF(categories.page_title, ''), excluded.page_title),
                            meta_description = COALESCE(NULLIF(categories.meta_description, ''), excluded.meta_description),
                            intro = COALESCE(NULLIF(categories.intro, ''), excluded.intro)");
    foreach ($categories as $category) {
        $copy = runtime_category_copy($category['slug'], $category['name'], $category['description']);
        $stmt->reset();
        $stmt->clear();
        $stmt->bindValue(':slug', $category['slug'], SQLITE3_TEXT);
        $stmt->bindValue(':name', $category['name'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $category['description'], SQLITE3_TEXT);
        $stmt->bindValue(':sort_order', $category['sort_order'], SQLITE3_INTEGER);
        $stmt->bindValue(':page_title', $copy['page_title'], SQLITE3_TEXT);
        $stmt->bindValue(':meta_description', $copy['meta_description'], SQLITE3_TEXT);
        $stmt->bindValue(':intro', $copy['intro'], SQLITE3_TEXT);
        $stmt->execute();
    }
    $rows = $db->query("SELECT slug, name, description FROM categories ORDER BY sort_order, id");
    $update = $db->prepare("UPDATE categories
                            SET description = COALESCE(NULLIF(description, ''), :description),
                                page_title = COALESCE(NULLIF(page_title, ''), :page_title),
                                meta_description = COALESCE(NULLIF(meta_description, ''), :meta_description),
                                intro = COALESCE(NULLIF(intro, ''), :intro)
                            WHERE slug = :slug");
    while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
        $copy = runtime_category_copy((string) $row['slug'], (string) $row['name'], (string) ($row['description'] ?? ''));
        $update->reset();
        $update->clear();
        $update->bindValue(':slug', $row['slug'], SQLITE3_TEXT);
        $update->bindValue(':description', $copy['description'], SQLITE3_TEXT);
        $update->bindValue(':page_title', $copy['page_title'], SQLITE3_TEXT);
        $update->bindValue(':meta_description', $copy['meta_description'], SQLITE3_TEXT);
        $update->bindValue(':intro', $copy['intro'], SQLITE3_TEXT);
        $update->execute();
    }
}
function runtime_category_copy(string $slug, string $name, string $description): array {
    $description = trim($description);
    $overrides = [
        'github' => [
            'description' => 'GitHub and Git workflows from the terminal, including pull requests, issues, releases, repositories, and API-driven automation.',
            'page_title' => 'Best CLIs for GitHub and Git',
            'meta_description' => 'Discover the best CLI tools for GitHub and Git workflows, from pull requests and issues to releases, repository automation, and API calls.',
            'intro' => 'These CLIs give developers and agents direct control over repositories, pull requests, issues, releases, and related Git workflows. Use this page to find the right command surface for source control, review loops, and repo automation.',
        ],
        'google-workspace' => [
            'description' => 'Google Workspace workflows from the terminal, including Gmail, Calendar, Drive, Docs, Sheets, and admin operations.',
            'page_title' => 'Best CLIs for Google Workspace',
            'meta_description' => 'Discover the best CLI tools for Gmail, Calendar, Drive, Docs, Sheets, and Google Workspace admin workflows.',
            'intro' => 'These CLIs let developers and agents read, change, and automate work inside Google Workspace from the shell. That includes email, calendars, files, documents, spreadsheets, chat, and admin surfaces.',
        ],
        'containers' => [
            'description' => 'Container, Kubernetes, and cluster workflows from the terminal, including image builds, registries, deployments, and cluster inspection.',
            'page_title' => 'Best CLIs for Containers and Kubernetes',
            'meta_description' => 'Discover the best CLI tools for Docker, containers, Kubernetes, cluster inspection, deployments, and image workflows.',
            'intro' => 'These CLIs cover container builds, registries, deployments, cluster inspection, logs, rollouts, and Kubernetes operations. They are especially useful when an agent needs a direct action layer for infra and deployment work.',
        ],
        'cloud' => [
            'description' => 'Cloud and storage workflows from the terminal, including provider resources, object storage, deployments, and account operations.',
            'page_title' => 'Best CLIs for Cloud and Storage',
            'meta_description' => 'Discover the best CLI tools for cloud infrastructure, storage, deployments, account operations, and provider-specific automation.',
            'intro' => 'These CLIs provide direct access to cloud providers, storage systems, deployments, and account workflows from the shell. They are useful when an agent needs to inspect state, make changes, and verify the result across infrastructure surfaces.',
        ],
        'security' => [
            'description' => 'Secrets, auth, vault, and security workflows from the terminal.',
            'page_title' => 'Best CLIs for Secrets, Auth, and Security',
            'meta_description' => 'Discover the best CLI tools for secrets management, authentication, vault workflows, access control, and security automation.',
            'intro' => 'These CLIs help developers and agents manage secrets, credentials, tokens, access control, and related security workflows. The strongest tools here expose machine-readable reads and carefully scoped mutation commands.',
        ],
        'media' => [
            'description' => 'Media, video, download, and playback workflows from the terminal.',
            'page_title' => 'Best CLIs for Media and Video',
            'meta_description' => 'Discover the best CLI tools for media downloads, video processing, metadata extraction, subtitles, and related terminal workflows.',
            'intro' => 'These CLIs cover media downloads, video workflows, playback-adjacent utilities, subtitles, metadata extraction, and related processing tasks from the shell. They are useful when an agent needs concrete file and media operations instead of GUI-only workflows.',
        ],
        'trading-crypto' => [
            'description' => 'Trading, crypto exchange, market data, and portfolio workflows from the terminal.',
            'page_title' => 'Best CLIs for Trading and Crypto',
            'meta_description' => 'Discover the best CLI tools for crypto exchanges, market data, account reads, trading flows, and portfolio automation.',
            'intro' => 'These CLIs expose trading, exchange, market data, and portfolio workflows directly in the terminal. They are high-leverage for agents, but also high-risk, so strong JSON output, clear exit behavior, and careful mutation boundaries matter here.',
        ],
    ];
    if (isset($overrides[$slug])) {
        return $overrides[$slug];
    }
    if ($description === '') {
        $description = "{$name} workflows from the terminal.";
    }
    return [
        'description' => $description,
        'page_title' => "Best CLIs for {$name}",
        'meta_description' => "Discover the best CLI tools for {$name}. Curated for developers and AI agents with install commands, compatibility signals, and AI analysis.",
        'intro' => "This category covers {$description} Browse curated CLI tools with install commands, official signals, compatibility flags, and AI analysis so you can find the right command surface quickly.",
    ];
}
function backfill_official_clis(SQLite3 $db): void {
    $existing = [
        'gh' => ['vendor_name' => 'GitHub', 'source_url' => 'https://cli.github.com', 'source_type' => 'github'],
        'glab' => ['vendor_name' => 'GitLab', 'source_url' => 'https://docs.gitlab.com/editor_extensions/gitlab_cli/', 'source_type' => 'github'],
        'kubectl' => ['vendor_name' => 'Kubernetes', 'source_url' => 'https://kubernetes.io/docs/reference/kubectl/', 'source_type' => 'github'],
        'aws-cli' => ['vendor_name' => 'AWS', 'source_url' => 'https://aws.amazon.com/cli/', 'source_type' => 'github'],
        'az' => ['vendor_name' => 'Microsoft Azure', 'source_url' => 'https://learn.microsoft.com/cli/azure/', 'source_type' => 'github'],
        'stripe' => ['vendor_name' => 'Stripe', 'source_url' => 'https://docs.stripe.com/stripe-cli', 'source_type' => 'github'],
        'doctl' => ['vendor_name' => 'DigitalOcean', 'source_url' => 'https://docs.digitalocean.com/reference/doctl/', 'source_type' => 'github'],
        'gcloud' => ['vendor_name' => 'Google Cloud', 'source_url' => 'https://cloud.google.com/sdk/gcloud', 'source_type' => 'github'],
        'vercel' => ['vendor_name' => 'Vercel', 'source_url' => 'https://vercel.com/docs/cli', 'source_type' => 'github'],
        'railway' => ['vendor_name' => 'Railway', 'source_url' => 'https://docs.railway.com/cli', 'source_type' => 'github'],
        'redis-cli' => ['vendor_name' => 'Redis', 'source_url' => 'https://redis.io/docs/latest/operate/rs/7.4/references/cli-utilities/redis-cli/', 'source_type' => 'docs'],
    ];
    $stmt = $db->prepare("UPDATE clis SET is_official = 1, vendor_name = COALESCE(NULLIF(vendor_name, ''), ?), source_url = COALESCE(NULLIF(source_url, ''), ?), source_type = COALESCE(NULLIF(source_type, ''), ?) WHERE slug = ?");
    foreach ($existing as $slug => $meta) {
        $stmt->reset();
        $stmt->clear();
        $stmt->bindValue(1, $meta['vendor_name'], SQLITE3_TEXT);
        $stmt->bindValue(2, $meta['source_url'], SQLITE3_TEXT);
        $stmt->bindValue(3, $meta['source_type'], SQLITE3_TEXT);
        $stmt->bindValue(4, $slug, SQLITE3_TEXT);
        $stmt->execute();
    }
}
function backfill_cli_aliases(SQLite3 $db): void {
    $stmt = $db->prepare("UPDATE clis SET aliases = COALESCE(NULLIF(aliases, ''), ?) WHERE slug = ?");
    foreach (cli_alias_seed_rows() as $slug => $aliases) {
        $stmt->reset();
        $stmt->clear();
        $stmt->bindValue(1, $aliases, SQLITE3_TEXT);
        $stmt->bindValue(2, $slug, SQLITE3_TEXT);
        $stmt->execute();
    }
}
function normalize_local_brand_icons(SQLite3 $db): void {
    $rows = $db->query("SELECT id, brand_icon FROM clis WHERE brand_icon IS NOT NULL AND trim(brand_icon) != ''");
    $stmt = $db->prepare("UPDATE clis SET brand_icon = ? WHERE id = ?");
    while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
        $brand_icon = local_brand_icon_slug($row['brand_icon'] ?? null);
        $stmt->reset();
        $stmt->clear();
        $stmt->bindValue(1, $brand_icon, $brand_icon === null ? SQLITE3_NULL : SQLITE3_TEXT);
        $stmt->bindValue(2, (int) $row['id'], SQLITE3_INTEGER);
        $stmt->execute();
    }
}
function refresh_search_index(SQLite3 $db): void {
    try {
        $db->exec("CREATE VIRTUAL TABLE IF NOT EXISTS clis_fts USING fts5(slug UNINDEXED, name, description, tags, vendor_name, install_cmd, aliases)");
        $db->exec("DELETE FROM clis_fts");
        $db->exec("INSERT INTO clis_fts (slug, name, description, tags, vendor_name, install_cmd, aliases)
                   SELECT slug,
                          coalesce(name, ''),
                          coalesce(description, ''),
                          coalesce(tags, ''),
                          coalesce(vendor_name, ''),
                          coalesce(install_cmd, ''),
                          coalesce(aliases, '')
                   FROM clis");
    } catch (Throwable $e) {
        error_log('clis.dev search index refresh failed: ' . $e->getMessage());
    }
}
function seed_vendor_clis(SQLite3 $db): void {
    $vendorClis = [
        [
            'slug' => 'basecamp',
            'name' => 'Basecamp CLI',
            'description' => 'Official Basecamp CLI for agent-friendly Basecamp workflows and account operations from the terminal.',
            'category_slug' => 'dev-tools',
            'install_cmd' => 'curl -fsSL https://basecamp.com/install-cli | bash',
            'website_url' => 'https://basecamp.com/agents',
            'source_url' => 'https://basecamp.com/agents',
            'language' => '',
            'vendor_name' => 'Basecamp',
            'brand_icon' => '',
            'source_type' => 'vendor',
            'tags' => 'basecamp,project-management,agents,official',
        ],
        [
            'slug' => 'sf',
            'name' => 'Salesforce CLI',
            'description' => 'Official Salesforce CLI for org management, metadata operations, deploy flows, and automation.',
            'category_slug' => 'dev-tools',
            'install_cmd' => 'See install docs',
            'website_url' => 'https://developer.salesforce.com/tools/salesforcecli',
            'source_url' => 'https://developer.salesforce.com/tools/salesforcecli',
            'language' => '',
            'vendor_name' => 'Salesforce',
            'brand_icon' => '',
            'source_type' => 'vendor',
            'tags' => 'salesforce,crm,deploy,official',
        ],
        [
            'slug' => 'acli',
            'name' => 'Atlassian CLI',
            'description' => 'Official Atlassian CLI for Jira and Confluence workflows from the terminal.',
            'category_slug' => 'dev-tools',
            'install_cmd' => 'See install docs',
            'website_url' => 'https://developer.atlassian.com/cloud/acli/',
            'source_url' => 'https://developer.atlassian.com/cloud/acli/',
            'language' => '',
            'vendor_name' => 'Atlassian',
            'brand_icon' => 'atlassian',
            'source_type' => 'vendor',
            'tags' => 'jira,confluence,atlassian,official',
        ],
        [
            'slug' => 'shopify-cli',
            'name' => 'Shopify CLI',
            'description' => 'Official Shopify CLI for apps, themes, Hydrogen storefronts, and local development workflows.',
            'category_slug' => 'dev-tools',
            'install_cmd' => 'npm install -g @shopify/cli@latest',
            'website_url' => 'https://shopify.dev/docs/api/shopify-cli',
            'source_url' => 'https://shopify.dev/docs/api/shopify-cli',
            'language' => '',
            'vendor_name' => 'Shopify',
            'brand_icon' => 'shopify',
            'source_type' => 'vendor',
            'tags' => 'shopify,commerce,themes,official',
        ],
        [
            'slug' => 'ldcli',
            'name' => 'LaunchDarkly CLI',
            'description' => 'Official LaunchDarkly CLI for feature flag, project, and environment workflows from the terminal.',
            'category_slug' => 'dev-tools',
            'install_cmd' => 'brew install launchdarkly/tap/ldcli',
            'website_url' => 'https://launchdarkly.com/docs/home/getting-started/ldcli',
            'source_url' => 'https://launchdarkly.com/docs/home/getting-started/ldcli',
            'language' => '',
            'vendor_name' => 'LaunchDarkly',
            'brand_icon' => '',
            'source_type' => 'vendor',
            'tags' => 'feature-flags,launchdarkly,release,official',
        ],
    ];
    $sql = "INSERT INTO clis (slug, name, description, category_slug, install_cmd, github_url, website_url, stars, language, has_mcp, has_skill, has_json, is_featured, tags, brand_icon, launched_at, is_official, source_type, source_url, vendor_name)
            VALUES (:slug, :name, :description, :category_slug, :install_cmd, NULL, :website_url, 0, :language, 0, 0, 0, 0, :tags, :brand_icon, NULL, 1, :source_type, :source_url, :vendor_name)
            ON CONFLICT(slug) DO UPDATE SET
              name = excluded.name,
              description = excluded.description,
              category_slug = excluded.category_slug,
              install_cmd = excluded.install_cmd,
              website_url = excluded.website_url,
              tags = excluded.tags,
              brand_icon = excluded.brand_icon,
              is_official = 1,
              source_type = excluded.source_type,
              source_url = excluded.source_url,
              vendor_name = excluded.vendor_name";
    $stmt = $db->prepare($sql);
    foreach ($vendorClis as $cli) {
        $stmt->reset();
        $stmt->clear();
        foreach ($cli as $key => $value) {
            if ($value === '') {
                $stmt->bindValue(':' . $key, null, SQLITE3_NULL);
            } else {
                $stmt->bindValue(':' . $key, $value, SQLITE3_TEXT);
            }
        }
        $stmt->execute();
    }
}
function seed_agent_harnesses(SQLite3 $db): void {
    $rows = [
        [
            'slug' => 'claude-code',
            'name' => 'Claude Code',
            'description' => 'Anthropic\'s terminal-native coding agent for repo understanding, edits, tests, and git workflows.',
            'category_slug' => 'agent-harnesses',
            'install_cmd' => 'npm install -g @anthropic-ai/claude-code',
            'github_url' => 'https://github.com/anthropics/claude-code',
            'website_url' => 'https://docs.anthropic.com/en/docs/claude-code/overview',
            'source_url' => 'https://docs.anthropic.com/en/docs/claude-code/overview',
            'stars' => 74911,
            'language' => 'TypeScript',
            'has_mcp' => 0,
            'has_skill' => 0,
            'has_json' => 0,
            'brand_icon' => '',
            'vendor_name' => 'Anthropic',
            'source_type' => 'github',
            'is_official' => 1,
            'tags' => 'ai,agent,coding,terminal,harness,official',
        ],
        [
            'slug' => 'codex-cli',
            'name' => 'Codex CLI',
            'description' => 'OpenAI\'s lightweight terminal coding agent for editing, running tasks, and agentic development loops.',
            'category_slug' => 'agent-harnesses',
            'install_cmd' => 'npm install -g @openai/codex',
            'github_url' => 'https://github.com/openai/codex',
            'website_url' => 'https://github.com/openai/codex',
            'source_url' => 'https://github.com/openai/codex',
            'stars' => 63681,
            'language' => 'Rust',
            'has_mcp' => 0,
            'has_skill' => 0,
            'has_json' => 0,
            'brand_icon' => '',
            'vendor_name' => 'OpenAI',
            'source_type' => 'github',
            'is_official' => 1,
            'tags' => 'ai,agent,coding,terminal,harness,official',
        ],
        [
            'slug' => 'opencode',
            'name' => 'OpenCode',
            'description' => 'Open-source terminal coding agent focused on fast local loops, tool orchestration, and AI-assisted development.',
            'category_slug' => 'agent-harnesses',
            'install_cmd' => 'npm install -g opencode-ai',
            'github_url' => 'https://github.com/opencode-ai/opencode',
            'website_url' => 'https://github.com/opencode-ai/opencode',
            'source_url' => 'https://github.com/opencode-ai/opencode',
            'stars' => 11281,
            'language' => 'TypeScript',
            'has_mcp' => 0,
            'has_skill' => 0,
            'has_json' => 0,
            'brand_icon' => '',
            'vendor_name' => 'OpenCode',
            'source_type' => 'github',
            'is_official' => 0,
            'tags' => 'ai,agent,coding,terminal,harness,open-source',
        ],
    ];
    $sql = "INSERT INTO clis (slug, name, description, category_slug, install_cmd, github_url, website_url, stars, language, has_mcp, has_skill, has_json, is_featured, tags, brand_icon, launched_at, is_official, source_type, source_url, vendor_name)
            VALUES (:slug, :name, :description, :category_slug, :install_cmd, :github_url, :website_url, :stars, :language, :has_mcp, :has_skill, :has_json, 0, :tags, :brand_icon, NULL, :is_official, :source_type, :source_url, :vendor_name)
            ON CONFLICT(slug) DO UPDATE SET
              name = excluded.name,
              description = excluded.description,
              category_slug = excluded.category_slug,
              install_cmd = excluded.install_cmd,
              github_url = excluded.github_url,
              website_url = excluded.website_url,
              stars = excluded.stars,
              language = excluded.language,
              tags = excluded.tags,
              brand_icon = excluded.brand_icon,
              is_official = excluded.is_official,
              source_type = excluded.source_type,
              source_url = excluded.source_url,
              vendor_name = excluded.vendor_name";
    $stmt = $db->prepare($sql);
    foreach ($rows as $row) {
        $stmt->reset();
        $stmt->clear();
        foreach ($row as $key => $value) {
            if (in_array($key, ['stars', 'has_mcp', 'has_skill', 'has_json', 'is_official'], true)) {
                $stmt->bindValue(':' . $key, (int) $value, SQLITE3_INTEGER);
            } elseif ($value === '') {
                $stmt->bindValue(':' . $key, null, SQLITE3_NULL);
            } else {
                $stmt->bindValue(':' . $key, $value, SQLITE3_TEXT);
            }
        }
        $stmt->execute();
    }
}
function seed_curated_clis(SQLite3 $db): void {
    $rows = [
        [
            'slug' => 'kraken-cli',
            'name' => 'Kraken CLI',
            'description' => 'Official Kraken CLI for market data, account operations, spot and futures trading, funding, and paper trading.',
            'category_slug' => 'trading-crypto',
            'install_cmd' => "curl --proto '=https' --tlsv1.2 -LsSf https://github.com/krakenfx/kraken-cli/releases/latest/download/kraken-cli-installer.sh | sh",
            'github_url' => 'https://github.com/krakenfx/kraken-cli',
            'website_url' => null,
            'source_url' => 'https://github.com/krakenfx/kraken-cli',
            'stars' => 0,
            'language' => 'Rust',
            'has_mcp' => 1,
            'has_skill' => 1,
            'has_json' => 1,
            'brand_icon' => '',
            'vendor_name' => 'Kraken',
            'source_type' => 'github',
            'is_official' => 1,
            'tags' => 'kraken,crypto,trading,market-data,exchange,official',
        ],
    ];
    $sql = "INSERT INTO clis (slug, name, description, category_slug, install_cmd, github_url, website_url, stars, language, has_mcp, has_skill, has_json, is_featured, tags, brand_icon, launched_at, is_official, source_type, source_url, vendor_name)
            VALUES (:slug, :name, :description, :category_slug, :install_cmd, :github_url, :website_url, :stars, :language, :has_mcp, :has_skill, :has_json, 0, :tags, :brand_icon, NULL, :is_official, :source_type, :source_url, :vendor_name)
            ON CONFLICT(slug) DO UPDATE SET
              name = excluded.name,
              description = excluded.description,
              category_slug = excluded.category_slug,
              install_cmd = excluded.install_cmd,
              github_url = COALESCE(NULLIF(clis.github_url, ''), excluded.github_url),
              website_url = excluded.website_url,
              language = excluded.language,
              has_mcp = excluded.has_mcp,
              has_skill = excluded.has_skill,
              has_json = excluded.has_json,
              tags = excluded.tags,
              brand_icon = excluded.brand_icon,
              is_official = excluded.is_official,
              source_type = excluded.source_type,
              source_url = excluded.source_url,
              vendor_name = excluded.vendor_name";
    $stmt = $db->prepare($sql);
    foreach ($rows as $cli) {
        $stmt->reset();
        $stmt->clear();
        foreach ($cli as $key => $value) {
            if ($value === null) {
                $stmt->bindValue(':' . $key, null, SQLITE3_NULL);
            } elseif (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, SQLITE3_INTEGER);
            } else {
                $stmt->bindValue(':' . $key, $value, SQLITE3_TEXT);
            }
        }
        $stmt->execute();
    }
}
