<?php

function page_home() {
    $cat_filter = $_GET['cat'] ?? null;
    $agent_filter = isset($_GET['agent']);
    $official_filter = isset($_GET['official']);
    $search_query = trim((string) ($_GET['q'] ?? ''));
    $normalized_search_query = normalize_search_text($search_query);
    $has_active_filters = (bool) ($cat_filter || $agent_filter || $official_filter || $search_query !== '');
    
    $clis = query("SELECT c.* FROM clis c " . cli_order_sql('c'));
    $categories = query("SELECT cat.*, COUNT(cl.id) as cli_count FROM categories cat LEFT JOIN clis cl ON cl.category_slug = cat.slug GROUP BY cat.id ORDER BY cat.sort_order, cat.id");
    $total_clis = query_val("SELECT COUNT(*) FROM clis");
    $agent_ready = query_val("SELECT COUNT(*) FROM clis WHERE has_mcp = 1 OR has_skill = 1");
    $official_count = query_val("SELECT COUNT(*) FROM clis WHERE is_official = 1");
    $build_home_url = function (?string $categorySlug = null, bool $agentOnly = false, bool $officialOnly = false) use ($search_query): string {
        $params = [];
        if ($categorySlug) $params['cat'] = $categorySlug;
        if ($agentOnly) $params['agent'] = '1';
        if ($officialOnly) $params['official'] = '1';
        if ($search_query !== '') $params['q'] = $search_query;
        return '/' . ($params ? '?' . http_build_query($params) : '');
    };
    $matches_cli_filters = static function (array $cli) use ($cat_filter, $agent_filter, $official_filter, $normalized_search_query): bool {
        if ($cat_filter && ($cli['category_slug'] ?? '') !== $cat_filter) return false;
        if ($agent_filter && empty($cli['has_mcp']) && empty($cli['has_skill'])) return false;
        if ($official_filter && empty($cli['is_official'])) return false;
        if ($normalized_search_query !== '' && !str_contains(cli_search_blob($cli), $normalized_search_query)) return false;
        return true;
    };
    $active_filter_title = 'Search results';
    if ($cat_filter) {
        $active_filter_title = (string) (query_val("SELECT name FROM categories WHERE slug = ?", [$cat_filter]) ?: $cat_filter);
    } elseif ($agent_filter) {
        $active_filter_title = 'Agent-ready CLIs';
    } elseif ($official_filter) {
        $active_filter_title = 'Official CLIs';
    }
    $initial_visible = 0;
    foreach ($clis as $cli) {
        if ($matches_cli_filters($cli)) $initial_visible += 1;
    }
    $hero_demos = [
        [
            'id' => 'hotfix',
            'label' => 'Ship a hotfix',
            'prompt' => 'Fix the login 500 and ship it.',
            'summary' => 'GitHub, Kubernetes, Argo CD, and curl chained by an AI agent to diagnose, patch, deploy, and verify.',
            'toast_title' => 'Hotfix shipped',
            'toast_body' => 'Illustrative demo: issue traced, deploy healthy, checks green.',
            'steps' => [
                ['kind' => 'command', 'text' => 'gh issue view 481 --json title,body'],
                ['kind' => 'output', 'text' => '#481 login returns 500 after deploy'],
                ['kind' => 'command', 'text' => 'kubectl logs deploy/web --tail=40'],
                ['kind' => 'output', 'text' => 'TypeError: cannot read properties of undefined (reading email)'],
                ['kind' => 'command', 'text' => 'rg "session.user.email" src && pnpm test auth'],
                ['kind' => 'success', 'text' => '2 files matched · auth tests passed'],
                ['kind' => 'command', 'text' => 'git commit -am "fix: guard missing session user" && git push'],
                ['kind' => 'command', 'text' => 'argocd app sync app-prod && argocd app wait app-prod'],
                ['kind' => 'success', 'text' => 'Healthy · Synced · rollout finished'],
                ['kind' => 'command', 'text' => 'curl -I https://app.example.com/login'],
                ['kind' => 'success', 'text' => 'HTTP/2 200 OK'],
            ],
        ],
        [
            'id' => 'deploy',
            'label' => 'Deploy app + domain',
            'prompt' => 'Deploy this demo app to the VPS and point the domain.',
            'summary' => 'doctl, ssh, rsync, certbot, and DNS calls stitched into one deploy flow that an agent can execute end to end.',
            'toast_title' => 'Deploy completed',
            'toast_body' => 'Illustrative demo: code live, DNS pointed, TLS verified.',
            'steps' => [
                ['kind' => 'command', 'text' => 'git push origin main'],
                ['kind' => 'command', 'text' => 'doctl compute droplet list --format Name,PublicIPv4'],
                ['kind' => 'output', 'text' => 'app-edge-01   203.0.113.24'],
                ['kind' => 'command', 'text' => 'rsync -az ./dist deploy@203.0.113.24:/srv/app/current'],
                ['kind' => 'command', 'text' => 'ssh deploy@203.0.113.24 "sudo nginx -t && sudo systemctl reload nginx"'],
                ['kind' => 'success', 'text' => 'nginx config ok · php-fpm healthy'],
                ['kind' => 'command', 'text' => 'curl -X POST https://api.cloudflare.com/.../dns_records'],
                ['kind' => 'success', 'text' => 'DNS record updated to 203.0.113.24'],
                ['kind' => 'command', 'text' => 'certbot --nginx -d app.example.com -d www.app.example.com'],
                ['kind' => 'command', 'text' => 'curl -I https://app.example.com'],
                ['kind' => 'success', 'text' => 'HTTP/2 200 OK · certificate active'],
            ],
        ],
        [
            'id' => 'workday',
            'label' => 'Run the workday',
            'prompt' => 'Triage inbox, update tickets, and post PR status.',
            'summary' => 'gws, jira, and gh turn a chat request into actual operating work across email, planning, and engineering.',
            'toast_title' => 'Workday triaged',
            'toast_body' => 'Illustrative demo: inbox cleared, tickets updated, team notified.',
            'steps' => [
                ['kind' => 'command', 'text' => 'gws gmail list --label urgent --limit 5'],
                ['kind' => 'output', 'text' => '3 urgent threads · 1 needs reply · 1 needs follow-up'],
                ['kind' => 'command', 'text' => 'jira issue list --assignee me --status "In Progress"'],
                ['kind' => 'output', 'text' => 'ACME-42 · ACME-57 · OPS-12'],
                ['kind' => 'command', 'text' => 'gh pr checks 481 --watch=false'],
                ['kind' => 'success', 'text' => 'CI green · preview deployed'],
                ['kind' => 'command', 'text' => 'jira transition ACME-42 done'],
                ['kind' => 'command', 'text' => 'gws gmail send --to team@example.com --subject "PR shipped"'],
                ['kind' => 'success', 'text' => 'Status posted · calendar and inbox updated'],
            ],
        ],
    ];
    
    ob_start();
    ?>
    <?php if (!$has_active_filters): ?>
    <!-- Hero -->
    <section class="pt-14 pb-12">
      <div class="max-w-6xl mx-auto px-4">
        <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(27rem,1fr)] gap-10 lg:gap-12 items-start">
          <div class="text-center lg:text-left">
            <p class="text-accent text-base font-medium tracking-[0.18em] uppercase mb-4">The interface layer for the agent era</p>
            <h1 class="text-5xl sm:text-7xl font-bold text-white mb-5 tracking-tight leading-tight">
              <span class="block">CLIs are how</span>
              <span class="block text-accent">AI agents</span>
              <span class="block">get work done</span>
            </h1>
            <p class="text-zinc-400 text-lg sm:text-xl mb-6 max-w-3xl leading-relaxed mx-auto lg:mx-0">
              The difference between chat and action is tools. CLIs let AI agents inspect systems, make changes, and verify the result.
            </p>

            <p class="text-zinc-500 text-base mb-3">See it in action:</p>
            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-2 mb-4 max-w-3xl mx-auto lg:mx-0">
              <?php foreach ($hero_demos as $index => $demo): ?>
                <button
                  type="button"
                  data-demo-trigger="<?= esc($demo['id']) ?>"
                  class="hero-demo-trigger px-4 py-2 rounded-full text-sm sm:text-base font-medium border transition-colors <?= $index === 0 ? 'bg-accent/20 text-accent border-accent/30' : 'bg-zinc-900 text-zinc-400 border-zinc-800 hover:border-zinc-700' ?>"
                >
                  <?= esc($demo['label']) ?>
                </button>
              <?php endforeach; ?>
            </div>

          </div>

          <div class="relative w-full lg:max-w-[34rem] lg:justify-self-end">
            <div class="absolute -inset-3 rounded-[1.75rem] bg-[radial-gradient(circle_at_top,rgba(0,255,136,.14),transparent_58%)] blur-2xl pointer-events-none"></div>
            <div class="relative pt-1" data-demo-root>
              <div class="flex items-center justify-between gap-3 mb-3">
                <p class="text-zinc-500 text-xs uppercase tracking-[0.18em]">Interactive demo</p>
                <button type="button" data-demo-replay class="inline-flex items-center gap-1.5 text-accent hover:underline text-sm shrink-0">
                  <?= icon('rotate-cw', 'w-4 h-4') ?> Replay
                </button>
              </div>

              <div class="relative rounded-2xl border border-zinc-800 bg-black/55 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-zinc-800 bg-zinc-950/95">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-400/80"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-400/80"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-accent/80"></span>
                  </div>
                  <div class="text-zinc-500 text-xs font-mono" data-demo-label><?= esc($hero_demos[0]['label']) ?></div>
                </div>
                <div class="p-4 h-[25rem] overflow-y-auto scrollbar-none" data-demo-viewport>
                  <div class="space-y-2 font-mono text-[13px] sm:text-sm leading-relaxed" data-demo-terminal></div>
                </div>
                <div class="hero-demo-toast hidden" data-demo-toast>
                  <p class="text-white font-semibold text-sm" data-demo-toast-title></p>
                  <p class="text-zinc-400 text-xs mt-1" data-demo-toast-body></p>
                </div>
              </div>
              <div class="flex items-center justify-between gap-3 mt-3 px-1">
                <p class="text-zinc-600 text-xs">Illustrative demo built from real CLI patterns.</p>
                <a href="/why" class="inline-flex items-center gap-1.5 text-accent hover:underline text-xs shrink-0">
                  <?= icon('zap', 'w-3.5 h-3.5') ?> Why CLIs?
                </a>
              </div>
            </div>
            <script<?= csp_nonce_attr() ?> type="application/json" id="hero-demo-data"><?= json_for_html_script($hero_demos) ?></script>
          </div>
        </div>

        <div class="mt-10 max-w-3xl mx-auto">
          <form action="/" method="get" class="mb-4">
            <?php if ($cat_filter): ?><input type="hidden" name="cat" value="<?= esc($cat_filter) ?>"><?php endif; ?>
            <?php if ($agent_filter): ?><input type="hidden" name="agent" value="1"><?php endif; ?>
            <?php if ($official_filter): ?><input type="hidden" name="official" value="1"><?php endif; ?>
            <div class="flex items-center bg-zinc-900/95 border border-zinc-800 rounded-2xl px-5 py-4 shadow-[0_20px_60px_rgba(0,0,0,.18)] focus-within:border-accent/50 transition-colors">
              <?= icon('search', 'w-5 h-5 text-zinc-500 mr-3 shrink-0') ?>
              <input
                type="text"
                name="q"
                value="<?= esc($search_query) ?>"
                placeholder="Search CLIs... (gmail, docker, kubernetes, stripe)"
                class="bg-transparent text-white text-lg w-full outline-none placeholder:text-zinc-600"
                data-live-search-input
                autocomplete="off"
              >
            </div>
          </form>
          <p class="text-zinc-600 text-base text-center">
            Or skip the search — <a href="<?= esc(GITHUB_REPO) ?>/tree/main/skills/clis-search" target="_blank" rel="noopener" class="text-accent hover:underline">install the agent skill</a> ·
            <a href="/api/clis" class="text-accent hover:underline font-mono text-sm">api/clis</a>
          </p>
        </div>
      </div>
    </section>
    <?php else: ?>
    <section class="pt-8 pb-4 border-b border-zinc-900">
      <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-start justify-between gap-4 flex-wrap mb-5">
          <div>
            <p class="text-zinc-500 text-xs uppercase tracking-[0.18em] mb-2">Filtered view</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-white" data-filter-heading><?= esc($active_filter_title) ?></h1>
          </div>
          <a href="/" class="text-sm text-accent hover:underline">Back to all</a>
        </div>

        <form action="/" method="get" class="max-w-3xl">
          <?php if ($cat_filter): ?><input type="hidden" name="cat" value="<?= esc($cat_filter) ?>"><?php endif; ?>
          <?php if ($agent_filter): ?><input type="hidden" name="agent" value="1"><?php endif; ?>
          <?php if ($official_filter): ?><input type="hidden" name="official" value="1"><?php endif; ?>
          <div class="flex items-center bg-zinc-900/95 border border-zinc-800 rounded-2xl px-5 py-4 shadow-[0_20px_60px_rgba(0,0,0,.18)] focus-within:border-accent/50 transition-colors">
            <?= icon('search', 'w-5 h-5 text-zinc-500 mr-3 shrink-0') ?>
            <input
              type="text"
              name="q"
              value="<?= esc($search_query) ?>"
              placeholder="Search within these results..."
              class="bg-transparent text-white text-lg w-full outline-none placeholder:text-zinc-600"
              data-live-search-input
              autocomplete="off"
            >
          </div>
        </form>
      </div>
    </section>
    <?php endif; ?>

    <!-- Filters + CLI List -->
    <section id="browse" class="py-6 border-t border-zinc-900 scroll-mt-6">
      <div class="max-w-6xl mx-auto px-4 lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:items-start lg:gap-8">
        <aside class="hidden lg:block lg:self-start">
          <div class="rounded-2xl border border-zinc-900 bg-zinc-950/55 p-2.5">
            <p class="text-zinc-500 text-[11px] uppercase tracking-[0.18em] mb-2 px-2">Browse categories</p>
            <div class="space-y-1.5">
              <a href="<?= esc($build_home_url()) ?>" data-filter-kind="all" data-filter-label="All CLIs" class="flex items-center justify-between gap-2 rounded-xl px-2.5 py-2 text-sm font-medium transition-colors <?= !$cat_filter && !$agent_filter && !$official_filter ? 'bg-accent/20 text-accent border border-accent/30' : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700' ?>">
                <span>All</span>
                <span data-filter-count class="text-xs font-mono <?= !$cat_filter && !$agent_filter && !$official_filter ? 'text-accent' : 'text-zinc-600' ?>"><?= esc((string) $total_clis) ?></span>
              </a>
              <a href="<?= esc($build_home_url(null, true)) ?>" data-filter-kind="agent" data-filter-label="Agent-ready CLIs" class="flex items-center justify-between gap-2 rounded-xl px-2.5 py-2 text-sm font-medium transition-colors <?= $agent_filter ? 'bg-accent/20 text-accent border border-accent/30' : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700' ?>">
                <span class="flex items-center gap-1.5"><?= icon('plug', 'w-3 h-3') ?> Agent-ready</span>
                <span data-filter-count class="text-xs font-mono <?= $agent_filter ? 'text-accent' : 'text-zinc-600' ?>"><?= esc((string) $agent_ready) ?></span>
              </a>
              <a href="<?= esc($build_home_url(null, false, true)) ?>" data-filter-kind="official" data-filter-label="Official CLIs" class="flex items-center justify-between gap-2 rounded-xl px-2.5 py-2 text-sm font-medium transition-colors <?= $official_filter ? 'bg-accent/20 text-accent border border-accent/30' : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700' ?>">
                <span class="flex items-center gap-1.5"><?= icon('shield-check', 'w-3 h-3') ?> Official</span>
                <span data-filter-count class="text-xs font-mono <?= $official_filter ? 'text-accent' : 'text-zinc-600' ?>"><?= esc((string) $official_count) ?></span>
              </a>
              <?php foreach ($categories as $cat): ?>
                <a href="/category/<?= esc($cat['slug']) ?>" data-filter-kind="category" data-filter-value="<?= esc($cat['slug']) ?>" data-filter-label="<?= esc($cat['name']) ?>" class="flex items-center justify-between gap-2 rounded-xl px-2.5 py-2 text-sm font-medium transition-colors <?= $cat_filter === $cat['slug'] ? 'bg-accent/20 text-accent border border-accent/30' : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700' ?>">
                  <span class="flex items-center gap-1.5"><?= cat_icon_class($cat['slug'], 'w-3.5 h-3.5') ?> <?= esc($cat['name']) ?></span>
                  <span data-filter-count class="text-xs font-mono <?= $cat_filter === $cat['slug'] ? 'text-accent' : 'text-zinc-600' ?>"><?= esc((string) $cat['cli_count']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </aside>

        <div class="min-w-0">
          <div class="lg:hidden -mx-4 px-4 overflow-x-auto scrollbar-none pb-3 mb-3">
            <div class="flex items-center gap-2 w-max">
              <a href="<?= esc($build_home_url()) ?>" data-filter-kind="all" data-filter-label="All CLIs" class="px-3.5 py-2 rounded-full text-base font-medium whitespace-nowrap transition-colors <?= !$cat_filter && !$agent_filter && !$official_filter ? 'bg-accent/20 text-accent border border-accent/30' : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700' ?>">All</a>
              <a href="<?= esc($build_home_url(null, true)) ?>" data-filter-kind="agent" data-filter-label="Agent-ready CLIs" class="flex items-center gap-1.5 px-3.5 py-2 rounded-full text-base font-medium whitespace-nowrap transition-colors <?= $agent_filter ? 'bg-accent/20 text-accent border border-accent/30' : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700' ?>">
                <?= icon('plug', 'w-3 h-3') ?> Agent-ready
              </a>
              <a href="<?= esc($build_home_url(null, false, true)) ?>" data-filter-kind="official" data-filter-label="Official CLIs" class="flex items-center gap-1.5 px-3.5 py-2 rounded-full text-base font-medium whitespace-nowrap transition-colors <?= $official_filter ? 'bg-accent/20 text-accent border border-accent/30' : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700' ?>">
                <?= icon('shield-check', 'w-3 h-3') ?> Official
              </a>
              <?php foreach ($categories as $cat): ?>
                <a href="/category/<?= esc($cat['slug']) ?>" data-filter-kind="category" data-filter-value="<?= esc($cat['slug']) ?>" data-filter-label="<?= esc($cat['name']) ?>" class="flex items-center gap-1.5 px-3.5 py-2 rounded-full text-base font-medium whitespace-nowrap transition-colors <?= $cat_filter === $cat['slug'] ? 'bg-accent/20 text-accent border border-accent/30' : 'bg-zinc-900 text-zinc-400 border border-zinc-800 hover:border-zinc-700' ?>">
                  <?= cat_icon($cat['slug']) ?> <?= esc($cat['name']) ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="flex items-center justify-between mb-4">
            <span class="text-sm text-zinc-500 font-mono" data-results-label><?= $initial_visible ?> results · sorted by stars</span>
          </div>
          
          <div class="space-y-1" id="cli-list">
          <?php $visible_rank = 0; ?>
          <?php foreach ($clis as $i => $cli): ?>
          <?php $search_blob = cli_search_blob($cli); ?>
          <?php $is_visible = $matches_cli_filters($cli); ?>
          <?php if ($is_visible) $visible_rank += 1; ?>
          <a
            href="/cli/<?= esc($cli['slug']) ?>"
            class="group flex items-center gap-4 rounded-lg px-4 py-3.5 hover:bg-zinc-900/80 transition-colors"
            data-cli-item
            data-search="<?= esc($search_blob) ?>"
            data-category="<?= esc($cli['category_slug']) ?>"
            data-agent-ready="<?= ($cli['has_mcp'] || $cli['has_skill']) ? '1' : '0' ?>"
            data-official="<?= !empty($cli['is_official']) ? '1' : '0' ?>"
            <?= $is_visible ? '' : 'style="display:none"' ?>
          >
            <span class="text-zinc-700 font-mono text-sm w-6 text-right shrink-0" data-cli-rank><?= $is_visible ? $visible_rank : $i + 1 ?></span>
            <div class="w-7 h-7 shrink-0 flex items-center justify-center text-zinc-500">
              <?= render_brand_icon($cli['brand_icon'] ?? null, cat_icon_class($cli['category_slug'], 'w-5 h-5'), 'w-5 h-5') ?>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <span class="font-semibold text-white group-hover:text-accent transition-colors text-lg"><?= esc($cli['name']) ?></span>
                <?php if ($cli['has_mcp']): ?>
                  <span class="flex items-center gap-0.5 text-[10px] bg-accent/10 text-accent px-1.5 py-0.5 rounded border border-accent/20"><?= icon('plug', 'w-2.5 h-2.5') ?> MCP</span>
                <?php endif; ?>
                <?php if ($cli['has_skill']): ?>
                  <span class="flex items-center gap-0.5 text-[10px] bg-accent/10 text-accent px-1.5 py-0.5 rounded border border-accent/20"><?= icon('target', 'w-2.5 h-2.5') ?> Skill</span>
                <?php endif; ?>
                <?php if (!empty($cli['is_official'])): ?>
                  <span class="flex items-center gap-0.5 text-[10px] bg-emerald-500/10 text-emerald-300 px-1.5 py-0.5 rounded border border-emerald-500/20"><?= icon('shield-check', 'w-2.5 h-2.5') ?> Official</span>
                <?php endif; ?>
                <?php if ($cli['has_json'] && !$cli['has_mcp'] && !$cli['has_skill']): ?>
                  <span class="flex items-center gap-0.5 text-[10px] bg-zinc-800 text-zinc-500 px-1.5 py-0.5 rounded border border-zinc-700"><?= icon('file-json', 'w-2.5 h-2.5') ?> JSON</span>
                <?php endif; ?>
              </div>
              <p class="text-zinc-500 text-base truncate"><?= esc($cli['description']) ?></p>
            </div>
            <div class="text-right shrink-0 hidden sm:block ml-auto min-w-[80px]">
              <?php if ((int) ($cli['stars'] ?? 0) > 0): ?>
                <div class="flex items-center justify-end gap-1 text-zinc-400 text-sm">
                  <?= icon('star', 'w-3 h-3') ?>
                  <span class="font-mono"><?= format_stars((int) $cli['stars']) ?></span>
                </div>
                <div class="text-zinc-600 text-xs font-mono mt-0.5"><?= esc($cli['language'] ?: ($cli['vendor_name'] ?? '')) ?></div>
              <?php elseif (!empty($cli['is_official'])): ?>
                <div class="flex items-center justify-end gap-1 text-emerald-300 text-sm">
                  <?= icon('shield-check', 'w-3 h-3') ?>
                  <span class="font-mono">Official</span>
                </div>
                <div class="text-zinc-600 text-xs font-mono mt-0.5"><?= esc($cli['vendor_name'] ?: strtoupper((string) $cli['source_type'])) ?></div>
              <?php endif; ?>
            </div>
          </a>
          <?php endforeach; ?>
          </div>

          <div class="text-center py-16 <?= $initial_visible === 0 ? '' : 'hidden' ?>" data-live-empty>
            <p class="text-zinc-500 text-lg">No CLIs match your search.</p>
            <button type="button" class="text-accent text-base hover:underline mt-2 inline-block" data-clear-search>Clear search</button>
          </div>
        
          <?php if (empty($clis)): ?>
            <div class="text-center py-16">
              <p class="text-zinc-500">No CLIs found.</p>
              <a href="/" class="text-accent text-sm hover:underline mt-2 inline-block">← Back to all</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Why CLIs + Agent CTA -->
    <section class="py-16">
      <div class="max-w-5xl mx-auto px-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 sm:p-12">
          <div class="max-w-2xl mx-auto text-center">
            <p class="text-accent text-base font-medium tracking-wide uppercase mb-3">Why CLIs?</p>
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">The interface layer for AI agents</h2>
            <p class="text-zinc-400 text-lg leading-relaxed mb-6">AI agents don't click buttons — they run commands. CLIs are the primitives. Custom skills teach your agent how to use them for your project. <a href="/why" class="text-accent hover:underline">Here's why that matters →</a></p>
            
            <div class="flex items-center justify-center gap-3 flex-wrap mb-6">
              <span class="flex items-center gap-1.5 text-sm bg-accent/10 text-accent px-3 py-1.5 rounded-full border border-accent/20"><?= icon('plug', 'w-3.5 h-3.5') ?> MCP Server</span>
              <span class="flex items-center gap-1.5 text-sm bg-accent/10 text-accent px-3 py-1.5 rounded-full border border-accent/20"><?= icon('target', 'w-3.5 h-3.5') ?> Agent Skill</span>
              <span class="flex items-center gap-1.5 text-sm bg-zinc-800 text-zinc-400 px-3 py-1.5 rounded-full border border-zinc-700"><?= icon('file-json', 'w-3.5 h-3.5') ?> JSON Output</span>
            </div>
            
            <div class="flex items-center justify-center gap-4 flex-wrap">
              <a href="/why" class="inline-flex items-center gap-2 bg-accent text-black font-bold px-5 py-2.5 rounded-lg hover:bg-accent/80 transition-colors text-sm">
                <?= icon('zap', 'w-4 h-4') ?> Why CLIs matter
              </a>
            </div>
            
            <div class="flex items-center justify-center gap-6 mt-6 text-sm text-zinc-600">
              <a href="/api/clis" class="text-accent/70 hover:text-accent font-mono flex items-center gap-1.5"><?= icon('terminal', 'w-3.5 h-3.5') ?> /api/clis</a>
              <a href="/llms.txt" class="text-accent/70 hover:text-accent font-mono flex items-center gap-1.5"><?= icon('file-json', 'w-3.5 h-3.5') ?> /llms.txt</a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
    $home_schema = json_for_html_script([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'CLIs.dev',
        'url' => 'https://clis.dev',
        'description' => SITE_DESC,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => 'https://clis.dev/search?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ]);
    $home_body = ob_get_clean();
    $home_body .= str_replace('__NONCE_ATTR__', csp_nonce_attr(), <<<'HTML'
<script__NONCE_ATTR__>
(() => {
  const input = document.querySelector('[data-live-search-input]');
  const items = Array.from(document.querySelectorAll('[data-cli-item]'));
  const resultsLabel = document.querySelector('[data-results-label]');
  const emptyState = document.querySelector('[data-live-empty]');
  const clearButton = document.querySelector('[data-clear-search]');
  const filterLinks = Array.from(document.querySelectorAll('[data-filter-kind]'));
  const filterHeading = document.querySelector('[data-filter-heading]');
  if (!input || !items.length || !resultsLabel || !emptyState) return;

  const params = new URLSearchParams(window.location.search);
  const state = {
    query: input.value.trim(),
    category: params.get('cat') || '',
    agentOnly: params.has('agent'),
    officialOnly: params.has('official'),
  };
  const ACTIVE_CLASSES = ['bg-accent/20', 'text-accent', 'border-accent/30'];
  const INACTIVE_CLASSES = ['bg-zinc-900', 'text-zinc-400', 'border-zinc-800'];
  const ACTIVE_COUNT_CLASSES = ['text-accent'];
  const INACTIVE_COUNT_CLASSES = ['text-zinc-600'];
  const syncHeading = () => {
    if (!filterHeading) return;
    if (state.category) {
      const link = filterLinks.find((item) => item.dataset.filterKind === 'category' && item.dataset.filterValue === state.category);
      filterHeading.textContent = link?.dataset.filterLabel || 'Filtered CLIs';
      return;
    }
    if (state.agentOnly) {
      filterHeading.textContent = 'Agent-ready CLIs';
      return;
    }
    if (state.officialOnly) {
      filterHeading.textContent = 'Official CLIs';
      return;
    }
    filterHeading.textContent = state.query ? 'Search results' : 'All CLIs';
  };

  const syncControls = () => {
    filterLinks.forEach((link) => {
      const kind = link.dataset.filterKind;
      const value = link.dataset.filterValue || '';
      const active =
        (kind === 'all' && !state.category && !state.agentOnly && !state.officialOnly) ||
        (kind === 'agent' && state.agentOnly) ||
        (kind === 'official' && state.officialOnly) ||
        (kind === 'category' && state.category === value && !state.agentOnly && !state.officialOnly);

      link.classList.remove(...(active ? INACTIVE_CLASSES : ACTIVE_CLASSES));
      link.classList.add(...(active ? ACTIVE_CLASSES : INACTIVE_CLASSES));
      const count = link.querySelector('[data-filter-count]');
      if (count) {
        count.classList.remove(...(active ? INACTIVE_COUNT_CLASSES : ACTIVE_COUNT_CLASSES));
        count.classList.add(...(active ? ACTIVE_COUNT_CLASSES : INACTIVE_COUNT_CLASSES));
      }
    });
    syncHeading();
  };

  const syncUrl = () => {
    const url = new URL(window.location.href);
    if (state.query) url.searchParams.set('q', state.query);
    else url.searchParams.delete('q');
    if (state.category) url.searchParams.set('cat', state.category);
    else url.searchParams.delete('cat');
    if (state.agentOnly) url.searchParams.set('agent', '1');
    else url.searchParams.delete('agent');
    if (state.officialOnly) url.searchParams.set('official', '1');
    else url.searchParams.delete('official');
    history.replaceState({}, '', url);
  };

  const applyFilter = () => {
    state.query = input.value.trim();
    const query = state.query.toLowerCase();
    let visible = 0;

    items.forEach((item) => {
      const haystack = item.dataset.search || '';
      const categoryMatch = !state.category || item.dataset.category === state.category;
      const agentMatch = !state.agentOnly || item.dataset.agentReady === '1';
      const officialMatch = !state.officialOnly || item.dataset.official === '1';
      const searchMatch = !query || haystack.includes(query);
      const match = categoryMatch && agentMatch && officialMatch && searchMatch;
      item.style.display = match ? '' : 'none';
      if (match) {
        visible += 1;
        const rank = item.querySelector('[data-cli-rank]');
        if (rank) rank.textContent = String(visible);
      }
    });

    resultsLabel.textContent = `${visible} result${visible === 1 ? '' : 's'} · sorted by stars`;
    emptyState.classList.toggle('hidden', visible !== 0);
    syncControls();
    syncUrl();
  };

  input.addEventListener('input', applyFilter);
  filterLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      const kind = link.dataset.filterKind;
      const value = link.dataset.filterValue || '';

      if (kind === 'all') {
        state.category = '';
        state.agentOnly = false;
        state.officialOnly = false;
      } else if (kind === 'agent') {
        state.category = '';
        state.agentOnly = true;
        state.officialOnly = false;
      } else if (kind === 'official') {
        state.category = '';
        state.agentOnly = false;
        state.officialOnly = true;
      } else if (kind === 'category') {
        state.category = value;
        state.agentOnly = false;
        state.officialOnly = false;
      }

      applyFilter();
    });
  });
  clearButton?.addEventListener('click', () => {
    input.value = '';
    input.focus();
    applyFilter();
  });

  document.querySelectorAll('[data-copy-install]').forEach((button) => {
    button.addEventListener('click', async () => {
      const text = button.dataset.copyInstall || '';
      if (!text) return;
      try {
        await navigator.clipboard.writeText(text);
      } catch (_) {
        // Ignore clipboard failures silently.
      }
    });
  });
  applyFilter();
})();

(() => {
  const root = document.querySelector('[data-demo-root]');
  const dataEl = document.getElementById('hero-demo-data');
  if (!root || !dataEl) return;

  const demos = JSON.parse(dataEl.textContent);
  const selectors = Array.from(document.querySelectorAll('[data-demo-trigger]'));
  const replay = root.querySelector('[data-demo-replay]');
  const labelEl = root.querySelector('[data-demo-label]');
  const viewportEl = root.querySelector('[data-demo-viewport]');
  const terminalEl = root.querySelector('[data-demo-terminal]');
  const toastEl = root.querySelector('[data-demo-toast]');
  const toastTitleEl = root.querySelector('[data-demo-toast-title]');
  const toastBodyEl = root.querySelector('[data-demo-toast-body]');
  if (!labelEl || !viewportEl || !terminalEl || !toastEl || !toastTitleEl || !toastBodyEl) return;

  const ACTIVE_CLASSES = ['bg-accent/20', 'text-accent', 'border-accent/30'];
  const INACTIVE_CLASSES = ['bg-zinc-900', 'text-zinc-400', 'border-zinc-800'];
  let activeDemoId = demos[0]?.id || '';
  let timers = [];

  const clearTimers = () => {
    timers.forEach((timer) => clearTimeout(timer));
    timers = [];
  };

  const schedule = (fn, delay) => {
    timers.push(setTimeout(fn, delay));
  };

  const stripCaret = () => {
    terminalEl.querySelectorAll('.hero-demo-caret').forEach((node) => node.remove());
  };

  const scrollToBottom = () => {
    viewportEl.scrollTop = viewportEl.scrollHeight;
  };

  const syncSelectors = () => {
    selectors.forEach((button) => {
      const active = button.dataset.demoTrigger === activeDemoId;
      button.classList.remove(...(active ? INACTIVE_CLASSES : ACTIVE_CLASSES));
      button.classList.add(...(active ? ACTIVE_CLASSES : INACTIVE_CLASSES));
    });
  };

  const showToast = (demo) => {
    toastTitleEl.textContent = demo.toast_title;
    toastBodyEl.textContent = demo.toast_body;
    toastEl.classList.remove('hidden');
  };

  const hideToast = () => {
    toastEl.classList.add('hidden');
  };

  const appendLine = (step, withCaret = false) => {
    stripCaret();
    const line = document.createElement('div');
    line.className = `hero-demo-line ${step.kind} whitespace-pre-wrap`;
    line.textContent = step.text;
    if (withCaret) {
      const caret = document.createElement('span');
      caret.className = 'hero-demo-caret';
      line.appendChild(caret);
    }
    terminalEl.appendChild(line);
    requestAnimationFrame(scrollToBottom);
  };

  const startDemo = (demoId) => {
    const demo = demos.find((item) => item.id === demoId) || demos[0];
    if (!demo) return;

    activeDemoId = demo.id;
    clearTimers();
    syncSelectors();
    hideToast();
    labelEl.textContent = demo.label;
    terminalEl.innerHTML = '';
    scrollToBottom();
    appendLine({ kind: 'prompt', text: demo.prompt });
    appendLine({ kind: 'output', text: demo.summary }, true);

    let delay = 850;
    demo.steps.forEach((step, index) => {
      schedule(() => appendLine(step, index === demo.steps.length - 1), delay);
      delay += step.kind === 'output' ? 850 : 1050;
    });

    schedule(() => {
      stripCaret();
      showToast(demo);
    }, delay + 350);

    schedule(() => {
      startDemo(demo.id);
    }, delay + 4200);
  };

  selectors.forEach((button) => {
    button.addEventListener('click', () => {
      startDemo(button.dataset.demoTrigger || demos[0]?.id || '');
    });
  });

  replay?.addEventListener('click', () => {
    startDemo(activeDemoId || demos[0]?.id || '');
  });

  startDemo(activeDemoId);
})();
</script>
HTML);
    echo html_wrap(SITE_NAME . ' — CLI tools for AI agents', $home_body, '', $home_schema);
}
