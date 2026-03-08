<?php

function render_cli_inline(string $text): string {
    $parts = preg_split('/(\[[^\]]+\]\((?:https?:\/\/|\/)[^)]+\)|`[^`]+`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
    $html = [];

    foreach ($parts as $part) {
        if ($part === '') continue;

        if (preg_match('/^\[([^\]]+)\]\(((?:https?:\/\/|\/)[^)]+)\)$/', $part, $m)) {
            $label = render_cli_inline($m[1]);
            $url = $m[2];
            $href = null;
            if (str_starts_with($url, '/')) {
                $href = $url;
            } else {
                $href = safe_external_url($url);
            }
            if ($href !== null) {
                $attrs = str_starts_with($href, '/')
                    ? ''
                    : ' target="_blank" rel="noopener"';
                $html[] = '<a href="' . esc($href) . '"' . $attrs . ' class="text-accent hover:underline">' . $label . '</a>';
                continue;
            }
        }

        if (preg_match('/^`([^`]+)`$/', $part, $m)) {
            $html[] = '<code class="bg-zinc-800 px-1.5 py-0.5 rounded text-accent text-xs">' . esc($m[1]) . '</code>';
            continue;
        }

        $html[] = esc($part);
    }

    return implode('', $html);
}

function render_cli_about(string $text): string {
    $blocks = preg_split('/\R{2,}/', trim($text)) ?: [];
    $html = [];
    $seen_content = false;

    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;

        $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $block))));
        if (!$lines) continue;

        if (str_starts_with($lines[0], '## ')) {
            $html[] = '<div class="pt-3"><div class="inline-flex items-center gap-2 rounded-full border border-accent/20 bg-accent/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-accent"><span class="w-1.5 h-1.5 rounded-full bg-accent"></span>' . esc(substr($lines[0], 3)) . '</div></div>';
            $lines = array_slice($lines, 1);
            if (!$lines) {
                continue;
            }
        }

        $is_list = true;
        foreach ($lines as $line) {
            if (!str_starts_with($line, '- ')) {
                $is_list = false;
                break;
            }
        }

        if ($is_list) {
            $items = [];
            foreach ($lines as $line) {
                $items[] = '<li class="flex items-start gap-2.5 leading-relaxed"><span class="mt-[0.45rem] h-1.5 w-1.5 shrink-0 rounded-full bg-accent"></span><span>' . render_cli_inline(substr($line, 2)) . '</span></li>';
            }
            $html[] = '<ul class="space-y-2 text-zinc-300">' . implode('', $items) . '</ul>';
            continue;
        }

        $classes = $seen_content
            ? 'text-zinc-400 leading-7'
            : 'text-zinc-300 leading-7';
        $html[] = '<p class="' . $classes . '">' . render_cli_inline(implode(' ', $lines)) . '</p>';
        $seen_content = true;
    }

    return implode('', $html);
}

function page_cli(string $slug) {
    $cli = query_row("SELECT * FROM clis WHERE slug = ?", [$slug]);
    if (!$cli) { http_response_code(404); return; }
    $cat = query_row("SELECT * FROM categories WHERE slug = ?", [$cli['category_slug']]);
    $github_url = safe_external_url($cli['github_url'] ?? null, ['github.com', 'www.github.com']);
    $website_url = safe_external_url($cli['website_url'] ?? null);
    $source_url = safe_external_url($cli['source_url'] ?? null);
    $is_official = !empty($cli['is_official']);
    $install_cmd = trim((string) ($cli['install_cmd'] ?? ''));
    
    ob_start();
    ?>
    <section class="py-8">
      <div class="max-w-3xl mx-auto px-4">
        <div class="mb-2 text-xs font-mono flex items-center gap-1 text-zinc-500">
          <a href="/" class="hover:text-white">home</a>
          <span class="text-zinc-700">/</span>
          <a href="/category/<?= esc($cli['category_slug']) ?>" class="hover:text-white"><?= esc($cli['category_slug']) ?></a>
          <span class="text-zinc-700">/</span>
          <span class="text-accent"><?= esc($slug) ?></span>
        </div>
        
	        <div class="mt-6 mb-6">
	          <div class="flex items-start justify-between mb-3">
	            <div>
	              <h1 class="text-2xl sm:text-3xl font-bold text-white"><?= esc($cli['name']) ?></h1>
	              <?php if ($is_official): ?>
	                <div class="mt-2 inline-flex items-center gap-1.5 text-xs bg-emerald-500/10 text-emerald-300 px-2 py-1 rounded-full border border-emerald-500/20">
	                  <?= icon('shield-check', 'w-3.5 h-3.5') ?> Official<?= !empty($cli['vendor_name']) ? ' · ' . esc($cli['vendor_name']) : '' ?>
	                </div>
	              <?php endif; ?>
	            </div>
	            <div class="flex items-center gap-2">
	              <?php if ((int) ($cli['stars'] ?? 0) > 0): ?>
	                <div class="flex items-center gap-1 text-zinc-400 text-sm">
	                  <?= icon('star', 'w-4 h-4') ?>
	                  <span class="font-mono font-bold"><?= format_stars((int) $cli['stars']) ?></span>
	                </div>
	              <?php elseif ($is_official): ?>
	                <div class="flex items-center gap-1 text-emerald-300 text-sm">
	                  <?= icon('shield-check', 'w-4 h-4') ?>
	                  <span class="font-mono font-bold">Official</span>
	                </div>
	              <?php endif; ?>
	              <div class="flex items-center gap-1.5">
	                <?php if ($github_url): ?>
	                  <a href="<?= esc($github_url) ?>" target="_blank" rel="noopener" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-600 hover:text-white transition-colors" title="GitHub">
	                    <?= icon('github', 'w-4 h-4') ?>
	                  </a>
	                <?php endif; ?>
	                <?php if ($source_url && $source_url !== $website_url): ?>
	                  <a href="<?= esc($source_url) ?>" target="_blank" rel="noopener" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-600 hover:text-white transition-colors" title="Official docs">
	                    <?= icon('shield-check', 'w-4 h-4') ?>
	                  </a>
	                <?php endif; ?>
	                <?php if ($website_url): ?>
	                  <a href="<?= esc($website_url) ?>" target="_blank" rel="noopener" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-600 hover:text-white transition-colors" title="Website">
	                    <?= icon('external-link', 'w-4 h-4') ?>
	                  </a>
	                <?php endif; ?>
	              </div>
	            </div>
	          </div>
          <p class="text-zinc-400 leading-relaxed"><?= esc($cli['description']) ?></p>
        </div>
        
	        <?php if ($install_cmd !== ''): ?>
	        <!-- Install -->
	        <div class="mb-5">
	          <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-4 font-mono text-sm flex items-center justify-between group">
	            <div><span class="text-accent mr-2">$</span><span class="text-white"><?= esc($install_cmd) ?></span></div>
	            <button data-copy-install="<?= esc($install_cmd) ?>" class="text-zinc-600 hover:text-white transition-colors p-1" title="Copy" type="button">
	              <?= icon('copy', 'w-4 h-4') ?>
	            </button>
	          </div>
	        </div>
	        <?php endif; ?>
        
        <!-- Info Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-5">
          <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-3">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1"><?= !empty($cli['language']) ? 'Language' : 'Vendor' ?></div>
            <div class="font-mono text-white text-sm"><?= esc($cli['language'] ?: ($cli['vendor_name'] ?: '—')) ?></div>
          </div>
          <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-3">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1"><?= (int) ($cli['stars'] ?? 0) > 0 ? 'Stars' : 'Source' ?></div>
            <div class="font-mono text-white text-sm"><?= (int) ($cli['stars'] ?? 0) > 0 ? number_format((int) $cli['stars']) : esc($cli['source_type'] ?: 'vendor') ?></div>
          </div>
          <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-3">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1">Category</div>
            <a href="/category/<?= esc($cli['category_slug']) ?>" class="text-accent text-sm hover:underline"><?= esc($cat['name'] ?? $cli['category_slug']) ?></a>
          </div>
          <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-3">
            <div class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1">Agent</div>
            <?php if ($cli['has_json'] || $cli['has_skill'] || $cli['has_mcp']): ?>
              <span class="text-accent text-sm flex items-center gap-1"><?= icon('check', 'w-3 h-3') ?> Ready</span>
            <?php else: ?>
              <span class="text-zinc-600 text-sm">—</span>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Agent Compat -->
        <?php if ($cli['has_mcp'] || $cli['has_skill'] || $cli['has_json']): ?>
        <div class="mb-5">
          <div class="text-[10px] text-zinc-500 uppercase tracking-wider mb-2">Agent Compatibility</div>
          <div class="flex flex-wrap gap-2">
            <?php foreach ([
              ['has_json', 'JSON Output', 'file-json', 'bg-accent/10 border border-accent/25 text-accent'],
              ['has_skill', 'Agent Skill', 'target', 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-300'],
              ['has_mcp', 'MCP Support', 'plug', 'bg-zinc-900 border border-emerald-500/15 text-emerald-200'],
            ] as [$key, $label, $ico, $active]): ?>
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm <?= $cli[$key] ? $active : 'bg-zinc-900 border border-zinc-800 text-zinc-600' ?>">
              <?= icon($cli[$key] ? 'check' : 'x', 'w-3.5 h-3.5') ?>
              <?= icon($ico, 'w-3.5 h-3.5') ?>
              <?= $label ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        
	        <!-- AI Analysis -->
	        <?php if (!empty($cli['long_description'])): ?>
	        <div class="mb-5">
	          <div class="text-[10px] text-zinc-500 uppercase tracking-wider mb-3">AI Analysis</div>
	          <div class="rounded-2xl border border-zinc-800 bg-zinc-950/60 p-4 sm:p-5 text-sm space-y-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">
	            <?= render_cli_about($cli['long_description']) ?>
	          </div>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php
    $cli_schema = json_for_html_script([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => $cli['name'],
        'description' => $cli['description'],
        'applicationCategory' => 'DeveloperApplication',
        'operatingSystem' => 'macOS, Linux, Windows',
        'url' => "https://clis.dev/cli/{$cli['slug']}",
        'downloadUrl' => $github_url ?? '',
        'programmingLanguage' => $cli['language'],
        'aggregateRating' => $cli['stars'] > 0 ? [
            '@type' => 'AggregateRating',
            'ratingValue' => min(5, round(log10(max($cli['stars'], 1)) * 1.2, 1)),
            'bestRating' => 5,
            'ratingCount' => $cli['stars'],
        ] : null,
    ]);
    echo html_wrap("{$cli['name']} — " . SITE_NAME, ob_get_clean(), $cli['description'], $cli_schema);
}
