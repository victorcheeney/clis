<?php
/**
 * clis.dev — front controller
 */

define('DB_PATH', getenv('CLIS_DB_PATH') ?: dirname(__DIR__) . '/data/clis.sqlite');
define('SITE_NAME', 'CLIs.dev');
define('SITE_DESC', 'Discover CLI tools for AI agents. The interface layer for the agent era.');
define('GITHUB_REPO', 'https://github.com/victorcheeney/clis');
define('ANALYTICS_SALT', trim((string) getenv('CLIS_ANALYTICS_SALT')));
define('SITE_BASE_URL', 'https://clis.dev');
define('RUNTIME_SCHEMA_VERSION', '2026-03-08.3');
define('CSP_NONCE', base64_encode(random_bytes(16)));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; font-src 'self'; script-src 'self' 'nonce-" . CSP_NONCE . "'; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

require __DIR__ . '/lib/runtime.php';
require __DIR__ . '/lib/view.php';
require __DIR__ . '/lib/pages.php';
require __DIR__ . '/lib/api.php';

ensure_runtime_schema();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

if ($uri === '/favicon.svg') { header('Content-Type: image/svg+xml'); readfile(__DIR__ . '/favicon.svg'); exit; }
elseif ($uri === '/favicon.ico') { http_response_code(204); exit; }
elseif ($uri === '/og-image.png') { header('Content-Type: image/png'); header('Cache-Control: public, max-age=86400'); readfile(__DIR__ . '/og-image.png'); exit; }
elseif ($uri === '/og.svg') { header('Content-Type: image/svg+xml'); readfile(__DIR__ . '/og.svg'); exit; }
elseif (str_starts_with($uri, '/assets/')) {
    $asset = realpath(__DIR__ . $uri);
    $root = realpath(__DIR__ . '/assets');
    if (!$asset || !$root || !str_starts_with($asset, $root) || !is_file($asset)) {
        http_response_code(404);
        exit;
    }
    $ext = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
    $types = ['svg' => 'image/svg+xml', 'png' => 'image/png', 'webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'js' => 'application/javascript', 'css' => 'text/css'];
    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=86400');
    readfile($asset);
    exit;
}
elseif ($uri === '/') { track_view('/'); page_home(); }
elseif ($uri === '/llms.txt') { page_llms_txt(); }
elseif ($uri === '/sitemap.xml') { page_sitemap(); }
elseif ($uri === '/robots.txt') { header('Content-Type: text/plain'); echo "User-agent: *\nAllow: /\nSitemap: https://clis.dev/sitemap.xml\n"; exit; }
elseif ($uri === '/rss.xml' || $uri === '/feed' || $uri === '/feed.xml') { page_rss(); }
elseif ($uri === '/llms-full.txt') { page_llms_full_txt(); }
elseif ($uri === '/api/clis') { api_clis(); }
elseif ($uri === '/api/search') { api_search(); }
elseif ($uri === '/api/admin/clis') { api_admin_clis_collection(); }
elseif (preg_match('#^/api/admin/clis/([a-z0-9-]+)$#', $uri, $m)) { api_admin_clis_item($m[1]); }
elseif ($uri === '/search') { track_view('/search'); page_search(); }
elseif ($uri === '/submit') { track_view('/submit'); page_submit(); }
elseif ($uri === '/why') { track_view('/why'); page_why(); }
elseif (preg_match('#^/category/([a-z0-9-]+)$#', $uri, $m)) { page_category($m[1]); }
elseif (preg_match('#^/cli/([a-z0-9-]+)$#', $uri, $m)) { track_view("/cli/{$m[1]}"); page_cli($m[1]); }
else { http_response_code(404); echo html_wrap('404', '<div class="text-center py-20"><h1 class="text-4xl font-bold text-white mb-4">404</h1><p class="text-zinc-500">Not found.</p></div>'); }
