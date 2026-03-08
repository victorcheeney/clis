#!/usr/bin/env php
<?php
/**
 * sync-stars.php — Update GitHub star counts for all CLIs
 *
 * Example:
 *   php scripts/sync-stars.php
 *
 * Uses the public GitHub repos API. If GITHUB_TOKEN is set, it will use it
 * for higher rate limits, but it can fall back to unauthenticated requests
 * for public repos when org auth policy blocks the token.
 */

$token = getenv('GITHUB_TOKEN');

if (!extension_loaded('curl')) {
    fwrite(STDERR, "Error: the curl extension is required.\n");
    exit(1);
}

$dbPath = getenv('CLIS_DB_PATH') ?: dirname(__DIR__) . '/data/clis.sqlite';
if (!file_exists($dbPath)) {
    die("Error: database not found at {$dbPath}. Start the site once to initialize it.\n");
}

$db = new SQLite3($dbPath);
$db->enableExceptions(true);
$db->exec('PRAGMA journal_mode=WAL');

$clis = [];
$result = $db->query("SELECT id, slug, github_url FROM clis WHERE github_url LIKE 'https://github.com/%'");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) $clis[] = $row;

echo "Syncing stars for " . count($clis) . " CLIs...\n";
echo $token ? "Using authenticated GitHub API when allowed.\n" : "Using unauthenticated public GitHub API.\n";
$updated = 0;
$errors = 0;

function github_repo_request(string $repo, ?string $token): array {
    $ch = curl_init("https://api.github.com/repos/{$repo}");
    $headers = [
        "User-Agent: clis.dev-sync",
        "Accept: application/vnd.github.v3+json",
    ];
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($resp === false) {
        return ['status' => 0, 'headers' => [], 'body' => ''];
    }

    $rawHeaders = substr($resp, 0, $headerSize);
    $body = substr($resp, $headerSize);
    $headers = [];
    foreach (preg_split("/\r\n|\n|\r/", trim($rawHeaders)) as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) !== 2) continue;
        $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
    }

    return ['status' => $status, 'headers' => $headers, 'body' => $body];
}

foreach ($clis as $cli) {
    // Extract owner/repo from URL
    $path = parse_url($cli['github_url'], PHP_URL_PATH);
    $path = trim($path, '/');
    $parts = explode('/', $path);
    if (count($parts) < 2) { echo "  SKIP {$cli['slug']}: bad URL\n"; continue; }
    $repo = $parts[0] . '/' . $parts[1];

    $resp = github_repo_request($repo, $token);
    $status = $resp['status'];
    $headers = $resp['headers'];
    $body = $resp['body'];

    if ($status === 403) {
        $remaining = (int) ($headers['x-ratelimit-remaining'] ?? -1);
        $needsAuthBypass = isset($headers['x-github-sso']);
        if (!$needsAuthBypass && $body !== '') {
            $payload = json_decode($body, true);
            $message = is_array($payload) ? (string) ($payload['message'] ?? '') : '';
            $needsAuthBypass = str_contains($message, 'forbids access via a fine-grained personal access tokens')
                || str_contains($message, 'forbids access via fine-grained personal access tokens')
                || str_contains($message, 'Resource protected by organization SAML enforcement');
        }
        if ($needsAuthBypass) {
            $fallback = github_repo_request($repo, null);
            if ($fallback['status'] === 200) {
                $status = 200;
                $body = $fallback['body'];
                echo "  PUB  {$cli['slug']}: fallback to public API\n";
            }
        } elseif ($remaining === 0) {
            echo "  ERR  {$cli['slug']}: HTTP 403\n";
            echo "  Rate limited! Stopping.\n";
            $errors++;
            break;
        }
    }

    if ($status === 401) {
        $fallback = github_repo_request($repo, null);
        if ($fallback['status'] === 200) {
            $status = 200;
            $body = $fallback['body'];
            echo "  PUB  {$cli['slug']}: fallback to public API\n";
        }
    }

    if ($status !== 200) {
        echo "  ERR  {$cli['slug']}: HTTP {$status}\n";
        $errors++;
        continue;
    }

    $data = json_decode($body, true);
    $stars = $data['stargazers_count'] ?? 0;
    $language = $data['language'] ?? null;

    $stmt = $db->prepare("UPDATE clis SET stars = ?, last_updated = datetime('now') WHERE id = ?");
    $stmt->bindValue(1, $stars);
    $stmt->bindValue(2, $cli['id']);
    $stmt->execute();

    // Also update language if we got it
    if ($language) {
        $stmt = $db->prepare("UPDATE clis SET language = ? WHERE id = ? AND (language IS NULL OR language = '')");
        $stmt->bindValue(1, $language);
        $stmt->bindValue(2, $cli['id']);
        $stmt->execute();
    }

    echo "  OK   {$cli['slug']}: {$stars} ★\n";
    $updated++;

    usleep(200000); // 200ms between requests = ~5 req/sec, well within rate limits
}

echo "\nDone. Updated: {$updated}, Errors: {$errors}\n";
