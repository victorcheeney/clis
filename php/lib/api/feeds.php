<?php

function page_llms_txt() {
    header('Content-Type: text/plain');
    $total = query_val("SELECT COUNT(*) FROM clis");
    echo "# CLIs.dev\n\n> CLI tools for AI agents. {$total} CLIs indexed.\n\n";
    echo "## Links\n- Full index with details: https://clis.dev/llms-full.txt\n- JSON API: https://clis.dev/api/clis\n\n";
    echo "## API\n- GET /api/clis — All CLIs (JSON)\n- GET /api/clis?category=agent-harnesses — Filter by category\n- GET /api/clis?agent — Agent-compatible only\n- GET /api/clis?official=1 — Official/vendor-backed only\n- GET /api/search?q=github%20cli — Search\n\n";
    echo "## Categories\n";
    foreach (query("SELECT * FROM categories ORDER BY sort_order") as $cat) echo "- [{$cat['name']}](https://clis.dev/category/{$cat['slug']})\n";
    echo "\n## Agent-Ready CLIs\n";
    foreach (query("SELECT * FROM clis WHERE has_mcp = 1 OR has_skill = 1 " . cli_order_sql()) as $cli) {
        $b = []; if ($cli['has_mcp']) $b[] = 'MCP'; if ($cli['has_skill']) $b[] = 'Skill';
        echo "- [{$cli['name']}](https://clis.dev/cli/{$cli['slug']}) — {$cli['description']} [" . implode(', ', $b) . "]\n";
    }
    echo "\n## All CLIs\n";
    foreach (query("SELECT * FROM clis " . cli_order_sql()) as $cli) {
        $metric = (int) ($cli['stars'] ?? 0) > 0 ? '★' . number_format((int) $cli['stars']) : (($cli['is_official'] ?? 0) ? 'Official' : strtoupper((string) ($cli['source_type'] ?? 'github')));
        echo "- [{$cli['name']}](https://clis.dev/cli/{$cli['slug']}) — {$metric} — {$cli['description']}\n";
    }
    exit;
}
function page_sitemap() {
    header('Content-Type: application/xml');
    $base = 'https://clis.dev';
    $today = date('Y-m-d');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (['/' => '1.0', '/why' => '0.9', '/submit' => '0.6'] as $path => $priority) {
        echo "<url><loc>{$base}{$path}</loc><lastmod>{$today}</lastmod><changefreq>weekly</changefreq><priority>{$priority}</priority></url>\n";
    }
    foreach (query("SELECT slug FROM categories ORDER BY sort_order") as $cat) {
        echo "<url><loc>{$base}/category/{$cat['slug']}</loc><lastmod>{$today}</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
    }
    foreach (query("SELECT slug FROM clis ORDER BY stars DESC") as $cli) {
        echo "<url><loc>{$base}/cli/{$cli['slug']}</loc><lastmod>{$today}</lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>\n";
    }
    echo "</urlset>\n";
    exit;
}
function page_rss() {
    header('Content-Type: application/rss+xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    $base = 'https://clis.dev';
    $clis = query("SELECT * FROM clis ORDER BY id DESC LIMIT 50");
    $build_date = date('r');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo <<<RSS
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
  <title>CLIs.dev — New CLIs</title>
  <link>{$base}</link>
  <description>CLI tools for AI agents. New tools indexed weekly.</description>
  <language>en-us</language>
  <lastBuildDate>{$build_date}</lastBuildDate>
  <atom:link href="{$base}/rss.xml" rel="self" type="application/rss+xml"/>
  <image>
    <url>{$base}/og-image.png</url>
    <title>CLIs.dev</title>
    <link>{$base}</link>
  </image>

RSS;
    foreach ($clis as $cli) {
        $name = htmlspecialchars($cli['name'], ENT_XML1);
        $desc = htmlspecialchars($cli['description'], ENT_XML1);
        $slug = htmlspecialchars($cli['slug'], ENT_XML1);
        $cat = htmlspecialchars($cli['category_slug'], ENT_XML1);
        $stars = number_format($cli['stars']);
        $install = htmlspecialchars($cli['install_cmd'] ?? '', ENT_XML1);
        $github = htmlspecialchars($cli['github_url'] ?? '', ENT_XML1);
        $badges = [];
        if ($cli['has_mcp']) $badges[] = '🔌 MCP';
        if ($cli['has_skill']) $badges[] = '🎯 Skill';
        if ($cli['has_json']) $badges[] = '📋 JSON';
        $badge_str = $badges ? ' · ' . implode(' ', $badges) : '';
        $content = htmlspecialchars("<p>{$cli['description']}</p>" .
            "<p>⭐ {$stars} stars{$badge_str}</p>" .
            ($install ? "<p>Install: <code>{$cli['install_cmd']}</code></p>" : '') .
            ($github ? "<p>GitHub: {$cli['github_url']}</p>" : ''), ENT_XML1);
        echo <<<ITEM
  <item>
    <title>{$name}</title>
    <link>{$base}/cli/{$slug}</link>
    <guid isPermaLink="true">{$base}/cli/{$slug}</guid>
    <description>{$desc} — {$stars}★{$badge_str}</description>
    <content:encoded>{$content}</content:encoded>
    <category>{$cat}</category>
  </item>

ITEM;
    }
    echo "</channel>\n</rss>\n";
    exit;
}
function page_llms_full_txt() {
    header('Content-Type: text/plain');
    $total = query_val("SELECT COUNT(*) FROM clis");
    $agent_count = query_val("SELECT COUNT(*) FROM clis WHERE has_mcp = 1 OR has_skill = 1");
    echo "# CLIs.dev — Full CLI Index\n\n";
    echo "> CLI tools for AI agents. {$total} CLIs indexed, {$agent_count} agent-ready.\n\n";
    echo "## API Endpoints\n";
    echo "- GET https://clis.dev/api/clis — All CLIs (JSON)\n";
    echo "- GET https://clis.dev/api/clis?category={slug} — Filter by category\n";
    echo "- GET https://clis.dev/api/clis?agent — Agent-compatible CLIs only\n";
    echo "- GET https://clis.dev/api/clis?official=1 — Official/vendor-backed CLIs only\n";
    echo "- GET https://clis.dev/api/search?q={query} — Search CLIs\n";
    echo "- GET https://clis.dev/llms.txt — Summary index\n";
    echo "- GET https://clis.dev/llms-full.txt — This file (full details)\n\n";
    $cats = query("SELECT * FROM categories ORDER BY sort_order");
    echo "## Categories\n";
    foreach ($cats as $cat) {
        $count = query_val("SELECT COUNT(*) FROM clis WHERE category_slug = ?", [$cat['slug']]);
        echo "- {$cat['name']} ({$count} CLIs) — https://clis.dev/category/{$cat['slug']}\n";
    }
    echo "\n---\n\n## All CLIs\n\n";
    foreach (query("SELECT c.*, cat.name as category_name FROM clis c LEFT JOIN categories cat ON cat.slug = c.category_slug " . cli_order_sql('c')) as $cli) {
        echo "### {$cli['name']}\n";
        echo "- **Description:** {$cli['description']}\n";
        echo "- **Category:** {$cli['category_name']}\n";
        echo "- **Install:** `{$cli['install_cmd']}`\n";
        echo "- **Language:** " . ($cli['language'] ?: '—') . "\n";
        echo "- **Source Type:** " . ($cli['source_type'] ?: 'github') . "\n";
        if ($cli['vendor_name']) echo "- **Vendor:** {$cli['vendor_name']}\n";
        if ((int) ($cli['stars'] ?? 0) > 0) echo "- **GitHub Stars:** " . number_format((int) $cli['stars']) . "\n";
        if ($cli['is_official']) echo "- **Official:** yes\n";
        if ($cli['github_url']) echo "- **GitHub:** {$cli['github_url']}\n";
        if ($cli['source_url']) echo "- **Source:** {$cli['source_url']}\n";
        if ($cli['website_url']) echo "- **Website:** {$cli['website_url']}\n";
        $compat = [];
        if ($cli['has_mcp']) $compat[] = 'MCP Server';
        if ($cli['has_skill']) $compat[] = 'Agent Skill';
        if ($cli['has_json']) $compat[] = 'JSON Output';
        echo "- **Agent Compatibility:** " . ($compat ? implode(', ', $compat) : 'None') . "\n";
        echo "- **URL:** https://clis.dev/cli/{$cli['slug']}\n\n";
    }
    exit;
}

// ========================================
// Admin API
// ========================================
