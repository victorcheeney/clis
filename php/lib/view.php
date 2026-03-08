<?php
function icon(string $name, string $class = 'w-4 h-4'): string {
    $icons = [
        'search' => '<path d="m21 21-4.3-4.3M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z"/>',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'github' => '<path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.4 5.4 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65S8.93 17.38 9 18v4"/><path d="M9 18c-4.51 2-5-2-7-2"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'terminal' => '<polyline points="4 17 10 11 4 5"/><line x1="12" x2="20" y1="19" y2="19"/>',
        'box' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'cpu' => '<rect width="16" height="16" x="4" y="4" rx="2"/><rect width="6" height="6" x="9" y="9" rx="1"/><path d="M15 2v2"/><path d="M15 20v2"/><path d="M2 15h2"/><path d="M2 9h2"/><path d="M20 15h2"/><path d="M20 9h2"/><path d="M9 2v2"/><path d="M9 20v2"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        'cloud' => '<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>',
        'zap' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'monitor' => '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z"/>',
        'folder' => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
        'bot' => '<path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/>',
        'git-branch' => '<line x1="6" x2="6" y1="3" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>',
        'tool' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z"/>',
        'plug' => '<path d="M12 22v-5"/><path d="M9 8V2"/><path d="M15 8V2"/><path d="M18 8v5a6 6 0 0 1-6 6v0a6 6 0 0 1-6-6V8Z"/>',
        'target' => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'file-json' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 12a1 1 0 0 0-1 1v1a1 1 0 0 1-1 1 1 1 0 0 1 1 1v1a1 1 0 0 0 1 1"/><path d="M14 18a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1 1 1 0 0 1-1-1v-1a1 1 0 0 0-1-1"/>',
        'external-link' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'copy' => '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'heart' => '<path d="m12 21-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.18Z"/>',
        'eye' => '<path d="M2.06 12.35a1 1 0 0 1 0-.7C3.9 7.18 7.58 4 12 4s8.1 3.18 9.94 7.65a1 1 0 0 1 0 .7C20.1 16.82 16.42 20 12 20s-8.1-3.18-9.94-7.65Z"/><circle cx="12" cy="12" r="3"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'filter' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'book-open' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        'trophy' => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
        'sparkles' => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/>',
        'rocket' => '<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.3 9.3"/><path d="m17 6 4 4"/>',
        'rotate-cw' => '<path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15.55-6.36L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15.55 6.36L3 16"/>',
        'shield-check' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg class="' . $class . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

// Category icons mapping
function cat_icon(string $slug): string {
    $map = [
        'ai-agents' => 'bot', 'agent-harnesses' => 'rocket', 'ai-coding' => 'cpu', 'file-management' => 'folder', 'github' => 'git-branch',
        'containers' => 'box', 'shell-utilities' => 'zap', 'system-monitoring' => 'cpu',
        'http-apis' => 'globe', 'databases' => 'database', 'data-processing' => 'file-json',
        'dev-tools' => 'wrench', 'google-workspace' => 'mail', 'cloud' => 'cloud',
        'cloud-gcp' => 'cloud', 'utilities' => 'tool', 'networking' => 'globe',
        'security' => 'shield-check', 'media' => 'monitor', 'package-managers' => 'box',
        'testing' => 'check', 'trading-crypto' => 'target',
    ];
    return icon($map[$slug] ?? 'terminal', 'w-5 h-5');
}
function cat_icon_class(string $slug, string $class): string {
    $map = [
        'ai-agents' => 'bot', 'agent-harnesses' => 'rocket', 'ai-coding' => 'cpu', 'file-management' => 'folder', 'github' => 'git-branch',
        'containers' => 'box', 'shell-utilities' => 'zap', 'system-monitoring' => 'cpu',
        'http-apis' => 'globe', 'databases' => 'database', 'data-processing' => 'file-json',
        'dev-tools' => 'wrench', 'google-workspace' => 'mail', 'cloud' => 'cloud',
        'cloud-gcp' => 'cloud', 'utilities' => 'tool', 'networking' => 'globe',
        'security' => 'shield-check', 'media' => 'monitor', 'package-managers' => 'box',
        'testing' => 'check', 'trading-crypto' => 'target',
    ];
    return icon($map[$slug] ?? 'terminal', $class);
}
function render_brand_icon(?string $brand_icon, string $fallback_html, string $size = 'w-5 h-5'): string {
    $brand_icon = local_brand_icon_slug($brand_icon);
    if ($brand_icon === null) return $fallback_html;
    $url = '/assets/brands/' . rawurlencode($brand_icon) . '.svg';
    return '<img src="' . esc($url) . '" alt="" class="' . esc($size) . '" loading="lazy">';
}

function current_page_url(): string {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    if ($request_uri === '') $request_uri = '/';
    return SITE_BASE_URL . $request_uri;
}

function csp_nonce_attr(): string {
    return ' nonce="' . esc(CSP_NONCE) . '"';
}

function site_wordmark(string $class = 'h-7 w-auto'): string {
    return '<img src="/assets/images/logo-wordmark-v2.svg" alt="CLIs.dev" class="' . esc($class) . '" loading="eager">';
}

function html_wrap(string $title, string $body, string $desc = '', string $schema = ''): string {
    $desc = esc($desc ?: SITE_DESC);
    $title = esc($title);
    $github_repo = esc(GITHUB_REPO);
    $canonical = esc(current_page_url());
    $og_image = esc(SITE_BASE_URL . '/og-image.png');
    $rss_url = esc(SITE_BASE_URL . '/rss.xml');
    $nonce_attr = csp_nonce_attr();
    $schema_tag = $schema ? "<script{$nonce_attr} type=\"application/ld+json\">{$schema}</script>" : '';
    $nav_wordmark = site_wordmark('h-7 w-auto');
    $footer_wordmark = site_wordmark('h-4 w-auto opacity-80');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="{$desc}">
<meta property="og:title" content="{$title}">
<meta property="og:description" content="{$desc}">
<meta property="og:type" content="website">
<meta property="og:url" content="{$canonical}">
<meta property="og:image" content="{$og_image}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="CLIs.dev - CLI tools for AI agents">
<meta property="og:site_name" content="CLIs.dev">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{$title}">
<meta name="twitter:description" content="{$desc}">
<meta name="twitter:image" content="{$og_image}">
<meta name="twitter:image:alt" content="CLIs.dev - CLI tools for AI agents">
<link rel="canonical" href="{$canonical}">
<link rel="alternate" type="application/rss+xml" title="CLIs.dev - New CLIs" href="{$rss_url}">
{$schema_tag}
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<script src="/assets/vendor/tailwindcss-browser.min.js"></script>
<script{$nonce_attr}>tailwind.config={theme:{extend:{colors:{accent:'#00ff88'}}}}</script>
<style>
html,body{overflow-x:hidden}
body{font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
.font-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,'Liberation Mono','Courier New',monospace}
.scrollbar-none::-webkit-scrollbar{display:none}
.scrollbar-none{-ms-overflow-style:none;scrollbar-width:none}
.hero-demo-trigger{transition:background-color .2s ease,border-color .2s ease,color .2s ease}
.hero-demo-toast{position:absolute;top:1rem;right:1rem;max-width:14rem;padding:.85rem 1rem;border:1px solid rgba(0,255,136,.24);border-radius:1rem;background:rgba(10,15,12,.95);box-shadow:0 12px 40px rgba(0,0,0,.3)}
.hero-demo-line{opacity:0;transform:translateY(4px);animation:hero-demo-line .28s ease forwards}
.hero-demo-line.prompt{color:#e4e4e7}
.hero-demo-line.command{color:#e4e4e7}
.hero-demo-line.output{color:#71717a}
.hero-demo-line.success{color:#86efac}
.hero-demo-line.prompt::before{content:'user> ';color:#60a5fa}
.hero-demo-line.command::before{content:'$ ';color:#00ff88}
.hero-demo-caret{display:inline-block;width:.55rem;height:1rem;margin-left:.2rem;background:#00ff88;vertical-align:-.15rem;animation:hero-demo-caret 1s steps(1) infinite}
@keyframes hero-demo-line{to{opacity:1;transform:translateY(0)}}
@keyframes hero-demo-caret{50%{opacity:0}}
@media (max-width: 767px){
  .hero-demo-toast{left:1rem;right:1rem;max-width:none}
}
</style>
<title>{$title}</title>
</head>
<body class="bg-[#0a0a0a] text-zinc-300 min-h-screen antialiased flex flex-col">
<nav class="border-b border-zinc-900 sticky top-0 bg-[#0a0a0a]/95 backdrop-blur-sm z-50">
  <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
    <a href="/" class="hover:opacity-80 transition-opacity" aria-label="CLIs.dev home">{$nav_wordmark}</a>
    <div class="flex items-center gap-2 sm:gap-4">
      <a href="/why" class="hidden sm:flex items-center gap-1.5 text-xs text-zinc-500 hover:text-white transition-colors">
        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Why CLIs
      </a>
      <a href="/submit" class="flex items-center gap-1.5 text-xs bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-700 px-3 py-1.5 rounded-lg transition-colors">
        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg> Submit CLI
      </a>
      <a href="{$github_repo}" target="_blank" rel="noopener" class="text-zinc-500 hover:text-white transition-colors" title="GitHub">
        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.4 5.4 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65S8.93 17.38 9 18v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>
      </a>
    </div>
  </div>
</nav>
<main class="flex-1">{$body}</main>
<footer class="border-t border-zinc-900 mt-auto py-8">
  <div class="max-w-5xl mx-auto px-4">
    <div class="flex flex-col items-center gap-4">
      <p class="text-zinc-500 text-xs">Open directory for CLI discovery, automation, and agent workflows.</p>
      <div class="flex items-center gap-4 text-zinc-600 text-xs">
        <a href="{$github_repo}" class="hover:text-white transition-colors">Open source</a>
        <span class="text-zinc-700">·</span>
        <a href="/api/clis" class="hover:text-white transition-colors font-mono">API</a>
        <span class="text-zinc-700">·</span>
        <a href="/llms.txt" class="hover:text-white transition-colors font-mono">llms.txt</a>
        <span class="text-zinc-700">·</span>
        <a href="/submit" class="hover:text-white transition-colors">Submit a CLI</a>
        <span class="text-zinc-700">·</span>
        <a href="/rss.xml" class="hover:text-white transition-colors">RSS</a>
      </div>
      <p class="text-zinc-600 text-[11px] max-w-md text-center mt-2 leading-relaxed">CLIs are sourced from public GitHub repositories. We haven't verified every tool - always review a CLI before running it.</p>
      <p class="text-zinc-600 text-[11px] max-w-md text-center leading-relaxed">Questions, corrections, or removal requests: <a href="{$github_repo}/issues" class="text-accent hover:underline">open an issue</a> or <a href="{$github_repo}/pulls" class="text-accent hover:underline">send a pull request</a>.</p>
      <p class="text-xs text-zinc-600 mt-1 inline-flex items-center gap-1.5">{$footer_wordmark}<span>© 2026</span></p>
    </div>
  </div>
</footer>
</body>
</html>
HTML;
}
