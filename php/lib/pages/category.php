<?php

function page_category(string $slug) {
    $cat = query_row("SELECT * FROM categories WHERE slug = ?", [$slug]);
    if (!$cat) {
        http_response_code(404);
        echo html_wrap('Category not found | ' . SITE_NAME, '<div class="text-center py-20"><h1 class="text-4xl font-bold text-white mb-4">404</h1><p class="text-zinc-500">Category not found.</p></div>');
        return;
    }

    $clis = query(
        "SELECT c.* FROM clis c
         WHERE c.category_slug = ? " . cli_order_sql('c'),
        [$slug]
    );
    $total = count($clis);
    $official_count = 0;
    $agent_count = 0;
    foreach ($clis as $cli) {
        if (!empty($cli['is_official'])) $official_count += 1;
        if (!empty($cli['has_json']) || !empty($cli['has_skill']) || !empty($cli['has_mcp'])) $agent_count += 1;
    }

    $page_title = trim((string) ($cat['page_title'] ?? '')) ?: ('Best CLIs for ' . $cat['name']);
    $meta_description = trim((string) ($cat['meta_description'] ?? '')) ?: ('Discover the best CLI tools for ' . $cat['name'] . '.');
    $intro = trim((string) ($cat['intro'] ?? '')) ?: trim((string) ($cat['description'] ?? ''));
    $item_list = [];
    foreach (array_slice($clis, 0, 24) as $index => $cli) {
        $item_list[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => 'https://clis.dev/cli/' . $cli['slug'],
            'name' => $cli['name'],
        ];
    }

    ob_start();
    ?>
    <section class="py-10">
      <div class="max-w-5xl mx-auto px-4">
        <div class="mb-3 text-xs font-mono flex items-center gap-1 text-zinc-500">
          <a href="/" class="hover:text-white">home</a>
          <span class="text-zinc-700">/</span>
          <span class="text-accent"><?= esc($slug) ?></span>
        </div>

        <div class="rounded-3xl border border-zinc-900 bg-zinc-950/60 p-6 sm:p-8 mb-8">
          <div class="flex items-center gap-2 mb-4 text-accent">
            <?= cat_icon_class($slug, 'w-5 h-5') ?>
            <span class="text-xs uppercase tracking-[0.18em] font-semibold">Category</span>
          </div>
          <h1 class="text-3xl sm:text-5xl font-bold text-white tracking-tight mb-4"><?= esc($page_title) ?></h1>
          <?php if ($intro !== ''): ?>
            <p class="text-zinc-400 text-base sm:text-lg leading-8 max-w-3xl"><?= esc($intro) ?></p>
          <?php endif; ?>
          <div class="mt-6 flex flex-wrap gap-2">
            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-sm text-zinc-300">
              <?= icon('terminal', 'w-4 h-4 text-accent') ?> <?= esc((string) $total) ?> CLIs
            </div>
            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-sm text-zinc-300">
              <?= icon('shield-check', 'w-4 h-4 text-emerald-300') ?> <?= esc((string) $official_count) ?> official
            </div>
            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-sm text-zinc-300">
              <?= icon('plug', 'w-4 h-4 text-accent') ?> <?= esc((string) $agent_count) ?> agent-ready
            </div>
            <a href="/why" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-sm text-zinc-300 hover:text-white hover:border-zinc-700 transition-colors">
              <?= icon('zap', 'w-4 h-4 text-accent') ?> Why CLIs
            </a>
          </div>
        </div>

        <?php if (!$clis): ?>
          <div class="rounded-2xl border border-zinc-900 bg-zinc-950/60 p-6 text-zinc-400">No CLI entries yet in this category.</div>
        <?php else: ?>
          <div class="grid gap-3 sm:grid-cols-2">
            <?php foreach ($clis as $cli): ?>
              <?php
                $github_url = safe_external_url($cli['github_url'] ?? null, ['github.com', 'www.github.com']);
                $website_url = safe_external_url($cli['website_url'] ?? null);
                $source_url = safe_external_url($cli['source_url'] ?? null);
                $active_badges = [];
                if (!empty($cli['has_json'])) $active_badges[] = ['JSON Output', 'file-json', 'text-accent'];
                if (!empty($cli['has_skill'])) $active_badges[] = ['Agent Skill', 'target', 'text-emerald-300'];
                if (!empty($cli['has_mcp'])) $active_badges[] = ['MCP Support', 'plug', 'text-emerald-200'];
              ?>
              <a href="/cli/<?= esc($cli['slug']) ?>" class="group block rounded-2xl border border-zinc-900 bg-zinc-950/60 p-5 hover:border-zinc-700 transition-colors">
                <div class="flex items-start justify-between gap-3 mb-3">
                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <?= render_brand_icon($cli['brand_icon'] ?? null, cat_icon_class($slug, 'w-4 h-4'), 'w-4 h-4') ?>
                      <h2 class="text-white font-semibold text-lg truncate group-hover:text-accent transition-colors"><?= esc($cli['name']) ?></h2>
                    </div>
                    <?php if (!empty($cli['is_official']) && !empty($cli['vendor_name'])): ?>
                      <div class="mt-2 inline-flex items-center gap-1.5 text-[11px] bg-emerald-500/10 text-emerald-300 px-2 py-1 rounded-full border border-emerald-500/20">
                        <?= icon('shield-check', 'w-3 h-3') ?> <?= esc($cli['vendor_name']) ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <?php if ((int) ($cli['stars'] ?? 0) > 0): ?>
                    <div class="flex items-center gap-1 text-zinc-500 text-xs shrink-0">
                      <?= icon('star', 'w-3.5 h-3.5') ?>
                      <span class="font-mono"><?= format_stars((int) $cli['stars']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <p class="text-zinc-400 text-sm leading-6 mb-4"><?= esc($cli['description']) ?></p>

                <?php if ($active_badges): ?>
                  <div class="flex flex-wrap gap-2 mb-4">
                    <?php foreach ($active_badges as [$label, $icon_name, $class]): ?>
                      <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-zinc-900 border border-zinc-800 text-xs <?= esc($class) ?>">
                        <?= icon($icon_name, 'w-3.5 h-3.5') ?> <?= esc($label) ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <div class="flex items-center justify-between gap-3 text-xs text-zinc-500">
                  <span class="font-mono truncate"><?= esc($cli['language'] ?: ($cli['vendor_name'] ?: 'CLI')) ?></span>
                  <span class="flex items-center gap-3 shrink-0">
                    <?php if ($github_url): ?><span><?= icon('github', 'w-3.5 h-3.5') ?></span><?php endif; ?>
                    <?php if ($source_url && $source_url !== $website_url): ?><span><?= icon('shield-check', 'w-3.5 h-3.5') ?></span><?php endif; ?>
                    <?php if ($website_url): ?><span><?= icon('external-link', 'w-3.5 h-3.5') ?></span><?php endif; ?>
                  </span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
    <?php
    $schema = json_for_html_script([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $page_title,
        'description' => $meta_description,
        'url' => 'https://clis.dev/category/' . $slug,
        'mainEntity' => [
            '@type' => 'ItemList',
            'numberOfItems' => $total,
            'itemListElement' => $item_list,
        ],
    ]);
    echo html_wrap($page_title . ' | ' . SITE_NAME, ob_get_clean(), $meta_description, $schema);
}
