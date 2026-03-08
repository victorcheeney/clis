<?php

function api_admin_auth(): bool {
    $key = getenv('CLIS_ADMIN_API_KEY') ?: '';
    if ($key === '') {
        return false;
    }

    $provided = '';
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $auth, $m)) {
        $provided = trim($m[1]);
    } elseif (!empty($_SERVER['HTTP_X_ADMIN_KEY'])) {
        $provided = trim((string) $_SERVER['HTTP_X_ADMIN_KEY']);
    }

    return $provided !== '' && hash_equals($key, $provided);
}
function api_admin_json_headers(): void {
    header('Content-Type: application/json');
}
function api_admin_require_method(string $method): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === $method) {
        return;
    }
    header('Allow: ' . $method);
    api_admin_fail(405, 'method not allowed', ['allowed' => $method]);
}
function api_admin_require_auth(): void {
    api_admin_json_headers();
    if (api_admin_auth()) {
        return;
    }
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}
function api_admin_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        api_admin_fail(400, 'invalid request body');
    }
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }
    try {
        $input = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        api_admin_fail(400, 'invalid JSON');
    }
    if (!is_array($input)) {
        api_admin_fail(400, 'invalid JSON');
    }
    return $input;
}
function api_admin_allowed_fields(): array {
    return [
        'slug',
        'name',
        'description',
        'category_slug',
        'install_cmd',
        'github_url',
        'website_url',
        'stars',
        'language',
        'has_mcp',
        'has_skill',
        'has_json',
        'is_featured',
        'is_official',
        'source_type',
        'source_url',
        'vendor_name',
        'tags',
        'brand_icon',
        'launched_at',
        'aliases',
        'long_description',
        'is_tui',
    ];
}
function api_admin_field_types(): array {
    return [
        'stars' => SQLITE3_INTEGER,
        'has_mcp' => SQLITE3_INTEGER,
        'has_skill' => SQLITE3_INTEGER,
        'has_json' => SQLITE3_INTEGER,
        'is_featured' => SQLITE3_INTEGER,
        'is_official' => SQLITE3_INTEGER,
        'is_tui' => SQLITE3_INTEGER,
    ];
}
function api_admin_bind_values(SQLite3Stmt $stmt, array $values): void {
    $field_types = api_admin_field_types();
    $fields = array_keys($values);
    foreach (array_values($values) as $i => $v) {
        $field = $fields[$i];
        $type = $field_types[$field] ?? (is_null($v) ? SQLITE3_NULL : SQLITE3_TEXT);
        $stmt->bindValue($i + 1, $v, $type);
    }
}
function api_admin_require_category(string $slug): bool {
    return (bool) query_val("SELECT COUNT(*) FROM categories WHERE slug = ?", [$slug]);
}
function api_admin_fail(int $status, string $error, array $extra = []): void {
    api_admin_json_headers();
    http_response_code($status);
    echo json_encode(array_merge(['error' => $error], $extra));
    exit;
}
function api_admin_normalize_payload_value(string $field, mixed $value): mixed {
    if (in_array($field, ['has_mcp', 'has_skill', 'has_json', 'is_featured', 'is_official', 'is_tui'], true)) {
        return !empty($value) ? 1 : 0;
    }
    if ($field === 'stars') {
        return (int) $value;
    }
    if (in_array($field, ['github_url', 'website_url', 'source_url'], true)) {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }
        $allowed_hosts = $field === 'github_url' ? ['github.com', 'www.github.com'] : [];
        $safe = safe_external_url($normalized, $allowed_hosts);
        if ($safe === null) {
            api_admin_fail(400, "invalid {$field}");
        }
        return $safe;
    }
    if ($field === 'brand_icon') {
        return local_brand_icon_slug($value);
    }
    if ($field === 'source_type') {
        $normalized = trim((string) $value);
        if (!in_array($normalized, ['docs', 'github', 'vendor'], true)) {
            api_admin_fail(400, 'invalid source_type');
        }
        return $normalized;
    }
    return $value;
}
function api_admin_get_cli(string $slug) {
    api_admin_require_method('GET');
    api_admin_require_auth();
    if (!$slug) api_admin_fail(400, 'slug required');
    $cli = query_row("SELECT * FROM clis WHERE slug = ?", [$slug]);
    if (!$cli) api_admin_fail(404, 'not found');
    api_admin_json_headers();
    echo json_encode($cli, JSON_PRETTY_PRINT);
    exit;
}
function api_admin_create_cli() {
    api_admin_require_method('POST');
    api_admin_require_auth();
    $payload = api_admin_json_body();
    if (empty($payload['slug']) || empty($payload['name'])) {
        api_admin_fail(400, 'slug and name required');
    }
    $defaults = [
        'slug' => $payload['slug'],
        'name' => $payload['name'],
        'description' => $payload['description'] ?? '',
        'category_slug' => $payload['category_slug'] ?? 'utilities',
        'install_cmd' => $payload['install_cmd'] ?? '',
        'github_url' => api_admin_normalize_payload_value('github_url', $payload['github_url'] ?? null),
        'website_url' => api_admin_normalize_payload_value('website_url', $payload['website_url'] ?? null),
        'stars' => (int) ($payload['stars'] ?? 0),
        'language' => $payload['language'] ?? null,
        'has_mcp' => !empty($payload['has_mcp']) ? 1 : 0,
        'has_skill' => !empty($payload['has_skill']) ? 1 : 0,
        'has_json' => !empty($payload['has_json']) ? 1 : 0,
        'is_featured' => !empty($payload['is_featured']) ? 1 : 0,
        'is_official' => !empty($payload['is_official']) ? 1 : 0,
        'source_type' => $payload['source_type'] ?? 'github',
        'source_url' => api_admin_normalize_payload_value('source_url', $payload['source_url'] ?? null),
        'vendor_name' => $payload['vendor_name'] ?? null,
        'tags' => $payload['tags'] ?? '',
        'brand_icon' => $payload['brand_icon'] ?? null,
        'launched_at' => $payload['launched_at'] ?? null,
        'aliases' => $payload['aliases'] ?? '',
        'long_description' => $payload['long_description'] ?? null,
        'is_tui' => !empty($payload['is_tui']) ? 1 : 0,
    ];
    if (!api_admin_require_category($defaults['category_slug'])) {
        api_admin_fail(400, 'invalid category_slug');
    }
    if (query_row("SELECT slug FROM clis WHERE slug = ?", [$defaults['slug']])) {
        api_admin_fail(409, 'slug already exists');
    }
    $fields = implode(', ', array_keys($defaults));
    $placeholders = implode(', ', array_fill(0, count($defaults), '?'));
    $stmt = db()->prepare("INSERT INTO clis ($fields) VALUES ($placeholders)");
    if (!$stmt) {
        api_admin_fail(500, 'insert prepare failed');
    }
    api_admin_bind_values($stmt, $defaults);
    $result = $stmt->execute();
    if (!$result) {
        api_admin_fail(500, 'insert failed');
    }
    api_admin_json_headers();
    echo json_encode(['status' => 'created', 'slug' => $payload['slug']]);
    exit;
}
function api_admin_update_cli(string $slug) {
    api_admin_require_method('PATCH');
    api_admin_require_auth();
    $payload = api_admin_json_body();
    if (!$slug) api_admin_fail(400, 'slug required');
    $existing = query_row("SELECT * FROM clis WHERE slug = ?", [$slug]);
    if (!$existing) api_admin_fail(404, 'not found');
    $allowed = api_admin_allowed_fields();
    $updates = [];
    foreach ($payload as $key => $value) {
        if ($key === 'slug') continue;
        if (!in_array($key, $allowed)) continue;
        $updates[$key] = api_admin_normalize_payload_value($key, $value);
    }
    if (empty($updates)) {
        api_admin_json_headers();
        echo json_encode(['status' => 'no_changes', 'slug' => $slug]);
        exit;
    }
    if (isset($updates['category_slug']) && !api_admin_require_category((string) $updates['category_slug'])) {
        api_admin_fail(400, 'invalid category_slug');
    }
    $set_clauses = [];
    foreach (array_keys($updates) as $field) {
        $set_clauses[] = "$field = ?";
    }
    $sql = "UPDATE clis SET " . implode(', ', $set_clauses) . " WHERE slug = ?";
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        api_admin_fail(500, 'update prepare failed');
    }
    $values = $updates;
    $values['slug'] = $slug;
    api_admin_bind_values($stmt, $values);
    $result = $stmt->execute();
    if (!$result) {
        api_admin_fail(500, 'update failed');
    }
    api_admin_json_headers();
    echo json_encode(['status' => 'updated', 'slug' => $slug, 'fields' => array_keys($updates)]);
    exit;
}
function api_admin_delete_cli(string $slug) {
    api_admin_require_method('DELETE');
    api_admin_require_auth();
    if (!$slug) api_admin_fail(400, 'slug required');
    $existing = query_row("SELECT * FROM clis WHERE slug = ?", [$slug]);
    if (!$existing) api_admin_fail(404, 'not found');
    $stmt = db()->prepare("DELETE FROM clis WHERE slug = ?");
    if (!$stmt) {
        api_admin_fail(500, 'delete prepare failed');
    }
    $stmt->bindValue(1, $slug, SQLITE3_TEXT);
    $result = $stmt->execute();
    if (!$result) {
        api_admin_fail(500, 'delete failed');
    }
    api_admin_json_headers();
    echo json_encode(['status' => 'deleted', 'slug' => $slug]);
    exit;
}
function api_admin_clis_collection(): void {
    api_admin_create_cli();
}
function api_admin_clis_item(string $slug): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        api_admin_get_cli($slug);
    }
    if ($method === 'PATCH') {
        api_admin_update_cli($slug);
    }
    if ($method === 'DELETE') {
        api_admin_delete_cli($slug);
    }
    header('Allow: GET, PATCH, DELETE');
    api_admin_fail(405, 'method not allowed', ['allowed' => 'GET, PATCH, DELETE']);
}
