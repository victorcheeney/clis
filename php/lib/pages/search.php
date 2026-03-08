<?php

function page_search() {
    $q = trim($_GET['q'] ?? '');
    $results = [];
    if ($q) {
        $results = search_clis($q, 50);
    }
    
    ob_start();
    ?>
    <section class="py-8">
      <div class="max-w-5xl mx-auto px-4">
        <form action="/search" method="get" class="max-w-lg mb-6">
          <div class="flex items-center bg-zinc-900 border border-zinc-800 rounded-lg px-4 py-3 focus-within:border-accent/50">
            <?= icon('search', 'w-4 h-4 text-zinc-500 mr-3') ?>
            <input type="text" name="q" value="<?= esc($q) ?>" placeholder="Search CLIs..."
              class="bg-transparent text-white w-full outline-none placeholder:text-zinc-600 text-sm" autofocus>
          </div>
        </form>
        <?php if ($q): ?>
          <?php if (count($results) > 0): ?>
            <p class="text-zinc-500 text-xs mb-4"><?= count($results) ?> results for "<span class="text-white"><?= esc($q) ?></span>" · sorted by relevance</p>
            <div class="space-y-1">
              <?php foreach ($results as $i => $cli): ?>
              <a href="/cli/<?= esc($cli['slug']) ?>" class="group flex items-center gap-4 rounded-lg px-4 py-3 hover:bg-zinc-900/80 transition-colors">
                <span class="text-zinc-700 font-mono text-xs w-5 text-right shrink-0"><?= $i + 1 ?></span>
                <div class="w-6 h-6 shrink-0 flex items-center justify-center text-zinc-500">
                  <?= render_brand_icon($cli['brand_icon'] ?? null, icon('terminal', 'w-4 h-4'), 'w-5 h-5') ?>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-0.5">
                    <span class="font-semibold text-white group-hover:text-accent text-sm"><?= esc($cli['name']) ?></span>
                    <?php if ($cli['has_mcp']): ?><span class="flex items-center gap-0.5 text-[10px] bg-accent/10 text-accent px-1.5 py-0.5 rounded border border-accent/20"><?= icon('plug', 'w-2.5 h-2.5') ?> MCP</span><?php endif; ?>
                    <?php if ($cli['has_skill']): ?><span class="flex items-center gap-0.5 text-[10px] bg-accent/10 text-accent px-1.5 py-0.5 rounded border border-accent/20"><?= icon('target', 'w-2.5 h-2.5') ?> Skill</span><?php endif; ?>
                    <?php if (!empty($cli['is_official'])): ?><span class="flex items-center gap-0.5 text-[10px] bg-emerald-500/10 text-emerald-300 px-1.5 py-0.5 rounded border border-emerald-500/20"><?= icon('shield-check', 'w-2.5 h-2.5') ?> Official</span><?php endif; ?>
                  </div>
                  <p class="text-zinc-500 text-sm truncate"><?= esc($cli['description']) ?></p>
                </div>
                <div class="text-right shrink-0 hidden sm:block">
                  <?php if ((int) ($cli['stars'] ?? 0) > 0): ?>
                    <div class="flex items-center gap-1 text-zinc-400 text-xs"><?= icon('star', 'w-3 h-3') ?><span class="font-mono"><?= format_stars((int) $cli['stars']) ?></span></div>
                  <?php elseif (!empty($cli['is_official'])): ?>
                    <div class="flex items-center gap-1 text-emerald-300 text-xs"><?= icon('shield-check', 'w-3 h-3') ?><span class="font-mono">Official</span></div>
                  <?php endif; ?>
                </div>
              </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-20">
              <p class="text-zinc-500 text-xs mb-8">0 results for "<span class="text-white"><?= esc($q) ?></span>"</p>
              <h2 class="text-2xl font-bold text-white mb-3">No CLIs for "<?= esc($q) ?>"?</h2>
              <p class="text-zinc-500 text-sm mb-6 max-w-md mx-auto">Know one that should be here? Submit it.</p>
              <div class="flex items-center justify-center gap-3 flex-wrap">
                <a href="/submit" class="inline-flex items-center gap-2 bg-accent text-black font-bold px-5 py-2.5 rounded-lg hover:bg-accent/80 transition-colors text-sm">
                  <?= icon('plus', 'w-4 h-4') ?> Submit a CLI
                </a>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>
    <?php
    echo html_wrap("Search: {$q} — " . SITE_NAME, ob_get_clean());
}
