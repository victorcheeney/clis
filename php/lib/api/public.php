<?php

function api_clis() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    $cat = $_GET['category'] ?? null;
    $agent_only = isset($_GET['agent']);
    $official_only = isset($_GET['official']);
    $sql = "SELECT slug, name, description, long_description, category_slug as category, install_cmd as install, github_url as github, website_url as website, source_url, stars, language, has_mcp, has_skill, has_json, brand_icon, is_official, is_tui, source_type, vendor_name FROM clis";
    $params = []; $where = [];
    if ($cat) { $where[] = "category_slug = ?"; $params[] = $cat; }
    if ($agent_only) { $where[] = "(has_mcp = 1 OR has_skill = 1)"; }
    if ($official_only) { $where[] = "is_official = 1"; }
    if ($where) { $sql .= " WHERE " . implode(' AND ', $where); }
    $sql .= ' ' . cli_order_sql();
    $results = query($sql, $params);
    foreach ($results as &$r) {
        $r['has_mcp'] = (bool)$r['has_mcp'];
        $r['has_skill'] = (bool)$r['has_skill'];
        $r['has_json'] = (bool)$r['has_json'];
        $r['is_official'] = (bool)$r['is_official'];
        $r['is_tui'] = (bool)($r['is_tui'] ?? false);
    }
    echo json_encode(['count' => count($results), 'clis' => $results], JSON_PRETTY_PRINT);
    exit;
}
function api_search() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    $q = trim($_GET['q'] ?? '');
    if (!$q) { echo json_encode(['count' => 0, 'clis' => []]); exit; }
    $results = search_clis($q, 20);
    $results = array_map(static function (array $row): array {
        return [
            'slug' => $row['slug'],
            'name' => $row['name'],
            'description' => $row['description'],
            'category' => $row['category_slug'],
            'install' => $row['install_cmd'],
            'stars' => $row['stars'],
            'has_mcp' => $row['has_mcp'],
            'has_skill' => $row['has_skill'],
            'has_json' => $row['has_json'],
            'is_official' => $row['is_official'],
            'is_tui' => $row['is_tui'] ?? 0,
            'source_type' => $row['source_type'],
            'vendor_name' => $row['vendor_name'],
        ];
    }, $results);
    foreach ($results as &$r) {
        $r['has_mcp'] = (bool)$r['has_mcp'];
        $r['has_skill'] = (bool)$r['has_skill'];
        $r['has_json'] = (bool)$r['has_json'];
        $r['is_official'] = (bool)$r['is_official'];
        $r['is_tui'] = (bool)$r['is_tui'];
    }
    echo json_encode(['count' => count($results), 'query' => $q, 'clis' => $results], JSON_PRETTY_PRINT);
    exit;
}
