<?php

function db(): SQLite3 {
    static $db = null;
    if (!$db) {
        $db = new SQLite3(DB_PATH);
        $db->enableExceptions(true);
        $db->exec('PRAGMA journal_mode=WAL');
    }
    return $db;
}


// Simple server-side analytics (no cookies, no JS, privacy-friendly)
function track_view(string $path): void {
    try {
        $referrer = normalize_referrer($_SERVER['HTTP_REFERER'] ?? null);
        $stmt = db()->prepare("INSERT INTO page_views (path, referrer, ua, ip_hash) VALUES (?, ?, ?, ?)");
        $stmt->bindValue(1, $path, SQLITE3_TEXT);
        $stmt->bindValue(2, $referrer, SQLITE3_TEXT);
        $stmt->bindValue(3, null, SQLITE3_NULL);
        $ip_hash = analytics_ip_hash();
        $stmt->bindValue(4, $ip_hash, $ip_hash === null ? SQLITE3_NULL : SQLITE3_TEXT);
        $stmt->execute();
        if (random_int(1, 100) === 1) {
            db()->exec("DELETE FROM page_views WHERE created_at < datetime('now', '-30 days')");
        }
    } catch (Exception $e) {
        error_log('clis.dev track_view failed: ' . $e->getMessage());
    } // never break the page for analytics
}
function query(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql); foreach ($params as $i => $v) $stmt->bindValue($i+1, $v);
    $result = $stmt->execute(); $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
    return $rows;
}
function query_row(string $sql, array $params = []): ?array { $r = query($sql, $params); return $r[0] ?? null; }
function query_val(string $sql, array $params = []) {
    $stmt = db()->prepare($sql); foreach ($params as $i => $v) $stmt->bindValue($i+1, $v);
    $row = $stmt->execute()->fetchArray(SQLITE3_NUM); return $row ? $row[0] : null;
}
function analytics_ip_hash(): ?string {
    if (ANALYTICS_SALT === '') return null;
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($ip === '') return null;
    return hash_hmac('sha256', date('Y-m-d') . '|' . $ip, ANALYTICS_SALT);
}
function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function normalize_search_text(string $value): string {
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = preg_replace('/[^\p{L}\p{N}\.\-\+\/@_]+/u', ' ', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return trim($value);
}
function cli_search_blob(array $cli): string {
    return normalize_search_text(implode(' ', array_filter([
        $cli['name'] ?? '',
        $cli['description'] ?? '',
        $cli['slug'] ?? '',
        $cli['category_slug'] ?? '',
        $cli['language'] ?? '',
        $cli['tags'] ?? '',
        $cli['vendor_name'] ?? '',
        $cli['install_cmd'] ?? '',
        $cli['source_url'] ?? '',
        $cli['aliases'] ?? '',
    ])));
}
function format_stars(int $n): string { return $n >= 1000 ? round($n/1000, $n >= 10000 ? 0 : 1) . 'k' : (string)$n; }
function cli_order_sql(string $alias = ''): string {
    $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
    return "ORDER BY COALESCE({$prefix}stars, 0) DESC, {$prefix}name ASC";
}
function cli_alias_seed_rows(): array {
    return [
        'gh' => 'github cli,github,git hub',
        'glab' => 'gitlab cli,gitlab',
        'kubectl' => 'kubernetes cli,k8s cli,kube ctl',
        'aws-cli' => 'aws cli,amazon web services cli',
        'az' => 'azure cli,microsoft azure cli',
        'doctl' => 'digitalocean cli,digital ocean cli',
        'gcloud' => 'google cloud cli,gcp cli',
        'vercel' => 'vercel cli',
        'railway' => 'railway cli',
        'redis-cli' => 'redis cli',
        'stripe' => 'stripe cli',
        'sf' => 'salesforce cli,salesforce',
        'shopify-cli' => 'shopify cli,shopify',
        'basecamp' => 'basecamp cli',
        'ldcli' => 'launchdarkly cli,launch darkly cli',
        'acli' => 'atlassian cli,jira cli,confluence cli',
        'claude-code' => 'claude code,claude,anthropic cli',
        'codex-cli' => 'codex cli,codex,openai codex',
        'opencode' => 'open code,opencode ai',
        'gws' => 'google workspace cli,workspace cli',
        'jira' => 'jira cli,atlassian jira cli',
        'glow' => 'markdown cli',
    ];
}
function search_terms(string $query): array {
    $normalized = normalize_search_text($query);
    if ($normalized === '') return [];
    $parts = preg_split('/\s+/u', $normalized) ?: [];
    return array_values(array_unique(array_filter($parts, static fn($term) => mb_strlen($term, 'UTF-8') > 0)));
}
function build_fts_query(string $query): ?string {
    $terms = search_terms($query);
    if (!$terms) return null;
    $ftsTerms = [];
    foreach ($terms as $term) {
        $escaped = str_replace('"', '""', $term);
        $ftsTerms[] = '"' . $escaped . '"*';
    }
    return implode(' AND ', $ftsTerms);
}
function search_clis(string $query, int $limit = 50): array {
    $query = trim($query);
    if ($query === '') return [];

    $db = db();
    $limit = max(1, min($limit, 100));
    $normalized = normalize_search_text($query);
    $like = '%' . $normalized . '%';
    $prefix = $normalized . '%';
    $aliasExact = '%,' . $normalized . ',%';
    $aliasPrefix = '%,' . $normalized . '%';
    $fts = build_fts_query($query);

    if ($fts !== null) {
        try {
            $sql = "SELECT c.*,
                           bm25(clis_fts, 8.0, 3.0, 1.8, 1.6, 1.2, 4.5) AS fts_rank,
                           CASE
                             WHEN lower(c.name) = ? THEN 500
                             WHEN lower(c.slug) = ? THEN 480
                             WHEN lower(c.name) LIKE ? THEN 420
                             WHEN lower(coalesce(c.vendor_name, '')) = ? THEN 360
                             WHEN (',' || lower(coalesce(c.aliases, '')) || ',') LIKE ? THEN 320
                             WHEN lower(coalesce(c.vendor_name, '')) LIKE ? THEN 260
                             WHEN (',' || lower(coalesce(c.aliases, '')) || ',') LIKE ? THEN 220
                             WHEN lower(coalesce(c.install_cmd, '')) LIKE ? THEN 160
                             ELSE 0
                           END AS lexical_boost
                    FROM clis_fts
                    JOIN clis c ON c.slug = clis_fts.slug
                    WHERE clis_fts MATCH ?
                    ORDER BY lexical_boost DESC, fts_rank ASC,
                             COALESCE(c.stars, 0) DESC,
                             c.name ASC
                    LIMIT ?";
            $stmt = $db->prepare($sql);
            $params = [$normalized, $normalized, $prefix, $normalized, $aliasExact, $prefix, $aliasPrefix, $like, $fts, $limit];
            foreach ($params as $index => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($index + 1, $value, $type);
            }
            $result = $stmt->execute();
            $rows = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                unset($row['fts_rank'], $row['lexical_boost']);
                $rows[] = $row;
            }
            if ($rows) return $rows;
        } catch (Throwable $e) {
        }
    }

    $sql = "SELECT *
            FROM clis
            WHERE (lower(name) LIKE ?
               OR lower(description) LIKE ?
               OR lower(coalesce(tags, '')) LIKE ?
               OR lower(coalesce(vendor_name, '')) LIKE ?
               OR lower(coalesce(install_cmd, '')) LIKE ?
               OR lower(coalesce(aliases, '')) LIKE ?
               OR lower(coalesce(source_url, '')) LIKE ?)
            ORDER BY CASE
                       WHEN lower(name) = ? THEN 500
                       WHEN lower(slug) = ? THEN 480
                       WHEN lower(name) LIKE ? THEN 420
                       WHEN lower(coalesce(vendor_name, '')) = ? THEN 360
                       WHEN (',' || lower(coalesce(aliases, '')) || ',') LIKE ? THEN 320
                       WHEN lower(coalesce(vendor_name, '')) LIKE ? THEN 260
                       WHEN (',' || lower(coalesce(aliases, '')) || ',') LIKE ? THEN 220
                       WHEN lower(coalesce(install_cmd, '')) LIKE ? THEN 160
                       ELSE 0
                     END DESC,
                     COALESCE(stars, 0) DESC,
                     name ASC
            LIMIT ?";
    $params = [$like, $like, $like, $like, $like, $like, $like, $normalized, $normalized, $prefix, $normalized, $aliasExact, $prefix, $aliasPrefix, $like, $limit];
    $stmt = $db->prepare($sql);
    foreach ($params as $index => $value) {
        $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
        $stmt->bindValue($index + 1, $value, $type);
    }
    $result = $stmt->execute();
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}
function json_for_html_script(array $payload): string {
    return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
function normalize_referrer(?string $referrer): ?string {
    if (!$referrer) return null;
    $parts = parse_url($referrer);
    if (!$parts || empty($parts['host'])) return null;
    $scheme = ($parts['scheme'] ?? 'https') === 'http' ? 'http' : 'https';
    return substr($scheme . '://' . strtolower($parts['host']), 0, 120);
}
function local_brand_icon_slug(?string $value): ?string {
    $value = trim((string) $value);
    if ($value === '' || !preg_match('/\A[a-z0-9-]+\z/', $value)) return null;
    $asset_path = dirname(__DIR__, 2) . '/assets/brands/' . $value . '.svg';
    return is_file($asset_path) ? $value : null;
}
function safe_external_url(?string $value, array $allowed_hosts = []): ?string {
    $value = trim((string)$value);
    if ($value === '') return null;
    if (!filter_var($value, FILTER_VALIDATE_URL)) return null;
    $parts = parse_url($value);
    $scheme = strtolower($parts['scheme'] ?? '');
    $host = strtolower($parts['host'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true) || $host === '') return null;
    if ($allowed_hosts && !in_array($host, $allowed_hosts, true)) return null;
    return $value;
}
