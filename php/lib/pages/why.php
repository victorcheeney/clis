<?php

function page_why() {
    $openclaw_cli_slugs = [
        'gog',
        'wacli',
        'imsg',
        'spogo',
        'sonoscli',
        'remindctl',
        'sag',
        'ordercli',
        'eightctl',
        'goplaces',
        'peekaboo',
        'summarize',
        'camsnap',
    ];
    $placeholders = implode(', ', array_fill(0, count($openclaw_cli_slugs), '?'));
    $openclaw_local_clis = [];
    foreach (query("SELECT slug FROM clis WHERE slug IN ($placeholders)", $openclaw_cli_slugs) as $row) {
        $openclaw_local_clis[(string) $row['slug']] = true;
    }
    $why_cli_link = static function (string $slug, string $label, ?string $external_url = null) use ($openclaw_local_clis): string {
        $code = '<code class="bg-zinc-800 px-1.5 py-0.5 rounded text-accent text-xs">' . esc($label) . '</code>';
        if (isset($openclaw_local_clis[$slug])) {
            return '<a href="/cli/' . esc($slug) . '" class="text-accent hover:underline">' . $code . '</a>';
        }
        if ($external_url !== null) {
            return '<a href="' . esc($external_url) . '" target="_blank" rel="noopener" class="text-accent hover:underline">' . $code . '</a>';
        }
        return $code;
    };
    ob_start();
    ?>
    <article class="py-12">
      <div class="max-w-3xl mx-auto px-4">
        <div class="mb-2 text-xs font-mono flex items-center gap-1 text-zinc-500">
          <a href="/" class="hover:text-white">home</a>
          <span class="text-zinc-700">/</span>
          <span class="text-accent">why</span>
        </div>

        <h1 class="text-3xl sm:text-4xl font-bold text-white mt-6 mb-4 tracking-tight">Why CLIs matter in the age of agents</h1>
        <p class="text-zinc-500 text-sm mb-10">A practical interface for agents to take action.</p>
        
        <div class="space-y-10 text-zinc-400 text-base leading-relaxed">
        
          <!-- 1. Why CLIs fit agents so well -->
          <div class="space-y-3">
            <h2 class="text-xl font-bold text-white mb-3">Why CLIs fit agents so well</h2>
            <p>Agents need an interface for action. CLIs already expose actions as commands, flags, arguments, stdout, stderr, and exit codes. That's a very natural contract for a model.</p>
            <p>They're lightweight and discoverable. An agent can try a command, get it wrong, run <code class="bg-zinc-800 px-1.5 py-0.5 rounded text-accent text-xs">--help</code>, inspect the output, and try again. It only loads what it needs, when it needs it.</p>
            <p>They're also composable and debuggable. Humans can run the same command, inspect the same output, pipe tools together, and verify what actually happened.</p>
          </div>

          <!-- 2. Where skills come in -->
          <div class="space-y-3">
            <h2 class="text-xl font-bold text-white mb-3">Where skills come in</h2>
            <p>A CLI like <a href="/cli/doctl" class="text-accent hover:underline"><code class="bg-zinc-800 px-1.5 py-0.5 rounded text-accent text-xs">doctl</code></a> knows how to talk to DigitalOcean. But it doesn't know about <em>your</em> project. Your droplet IDs, your DNS zones, your deployment workflow.</p>
            <p>A skill gives the agent extra capability or context. One of the most useful patterns is using skills to teach the agent how to use a CLI in the context of your project.</p>
            <p>The CLI stays generic. The skill captures how your team actually uses it.</p>
          </div>

          <!-- 3. How the learning loop works -->
          <div class="space-y-3">
            <h2 class="text-xl font-bold text-white mb-3">How the learning loop works</h2>
            <p>You tell the agent what you want done. It tries the CLI, gets it wrong, runs <code class="bg-zinc-800 px-1.5 py-0.5 rounded text-accent text-xs">--help</code>, learns the command, and tries again. Once it works, you say "remember this" and it writes a skill for next time.</p>
            <p>That's <strong class="text-white">progressive disclosure</strong>. The agent loads only what it needs, when it needs it. The first time is messy. Then the workflow gets written down. After that, the agent can reuse it.</p>
            <p>One practical difference is how much context gets loaded up front. MCP front-loads more tool definition and context. CLIs let the agent learn by doing — and build its own documentation as it goes.</p>
          </div>

          <!-- 4. Case study: OpenClaw -->
          <div class="space-y-3">
            <h2 class="text-xl font-bold text-white mb-3">Case study: how CLIs power OpenClaw</h2>
            <p><a href="https://github.com/steipete" target="_blank" class="text-accent hover:underline">Peter Steinberger</a> built <a href="https://github.com/openclaw/openclaw" target="_blank" class="text-accent hover:underline">OpenClaw</a>, a personal AI agent that can chat through WhatsApp, Telegram, Discord, and perform actions across services.</p>
            <p>Before building OpenClaw, Peter had already built a large set of dedicated CLIs for the services he wanted to interact with.</p>
            <ul class="grid gap-x-8 gap-y-2 sm:grid-cols-2 text-sm text-zinc-400 pl-5 list-disc">
              <li>Google Workspace: <?= $why_cli_link('gog', 'gog', 'https://github.com/steipete/gogcli') ?></li>
              <li>WhatsApp: <?= $why_cli_link('wacli', 'wacli', 'https://github.com/steipete/wacli') ?></li>
              <li>iMessage: <?= $why_cli_link('imsg', 'imsg', 'https://github.com/steipete/imsg') ?></li>
              <li>Spotify: <?= $why_cli_link('spogo', 'spogo', 'https://github.com/steipete/spogo') ?></li>
              <li>Sonos: <?= $why_cli_link('sonoscli', 'sonoscli', 'https://github.com/steipete/sonoscli') ?></li>
              <li>Apple Reminders: <?= $why_cli_link('remindctl', 'remindctl', 'https://github.com/steipete/remindctl') ?></li>
              <li>ElevenLabs TTS: <?= $why_cli_link('sag', 'sag', 'https://github.com/steipete/sag') ?></li>
              <li>Food delivery: <?= $why_cli_link('ordercli', 'ordercli', 'https://github.com/steipete/ordercli') ?></li>
              <li>Eight Sleep: <?= $why_cli_link('eightctl', 'eightctl', 'https://github.com/steipete/eightctl') ?></li>
              <li>Google Places: <?= $why_cli_link('goplaces', 'goplaces', 'https://github.com/steipete/goplaces') ?></li>
              <li>Screenshots: <?= $why_cli_link('peekaboo', 'Peekaboo', 'https://github.com/steipete/Peekaboo') ?></li>
              <li>URL summarization: <?= $why_cli_link('summarize', 'summarize', 'https://github.com/steipete/summarize') ?></li>
              <li>RTSP cameras: <?= $why_cli_link('camsnap', 'camsnap', 'https://github.com/steipete/camsnap') ?></li>
            </ul>
            <p>When he later built OpenClaw, those CLIs gave the agent real capabilities. Peter then wrapped them in skills so the agent could use them in the context of his own workflows.</p>
            <p>OpenClaw has no native MCP support by design.</p>
            <p>On the <a href="https://www.youtube.com/watch?v=YFjfBk8HI5o&t=9535s" target="_blank" class="text-accent hover:underline">Lex Fridman Podcast (#491)</a>, Peter explained why:</p>
            <div class="bg-zinc-900 border-l-2 border-accent rounded-r-lg px-6 py-5 my-4">
              <p class="text-white text-base italic leading-relaxed">"If you want to extend the model with more features, you just build a CLI, and the model can call the CLI. It probably gets it wrong, calls the help menu, and then on demand loads into the context what it needs to use the CLI."</p>
              <p class="text-zinc-500 text-xs mt-3">— Peter Steinberger, <a href="https://www.youtube.com/watch?v=YFjfBk8HI5o&t=9564s" target="_blank" class="text-accent hover:underline">Lex Fridman Podcast #491 at 2:39:24</a></p>
            </div>
          </div>

          <!-- 5. What about MCP? -->
          <div class="space-y-3">
            <h2 class="text-xl font-bold text-white mb-3">What about MCP?</h2>
            <p>MCP has real use cases. Enterprise auth with OAuth and audit trails. Multi-tenant SaaS with fine-grained access control. Clients without shell access. Those are all valid cases.</p>
            <p>But for most developer workflows, MCP has a fundamental problem: it dumps every tool definition into your context window the moment you connect.</p>
            <p><a href="https://github.blog/changelog/2026-01-28-github-mcp-server-new-projects-tools-oauth-scope-filtering-and-new-features/" target="_blank" class="text-accent hover:underline">GitHub's own blog</a> confirmed their MCP server was using <strong class="text-white">~46,000 tokens</strong> before a January 2026 consolidation cut it in half. One developer <a href="https://www.reddit.com/r/ClaudeCode/comments/1mwxfit/" target="_blank" class="text-accent hover:underline">reported</a> MCP servers consuming <strong class="text-white">41.6% of Claude's context window</strong> before asking a single question.</p>
            <p>People are even building tools to convert MCP servers into CLIs — <a href="https://github.com/steipete/mcporter" target="_blank" class="text-accent hover:underline">mcporter</a> and <a href="https://www.philschmid.de/mcp-cli" target="_blank" class="text-accent hover:underline">mcp-cli</a>.</p>
          </div>

          <!-- 6. Build your toolkit -->
          <div class="space-y-3">
            <h2 class="text-xl font-bold text-white mb-3">Build your toolkit</h2>
            <p>Three steps:</p>
            <div class="space-y-4 my-4">
              <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <div class="flex items-start gap-3">
                  <span class="text-accent font-bold text-lg mt-0.5">1.</span>
                  <div>
                    <h3 class="font-semibold text-white mb-1">Find CLIs for the services you use</h3>
                    <p class="text-zinc-400 text-sm">GitHub, Kubernetes, Google Workspace, Cloudflare, your database — there's probably a CLI for it already. <a href="/" class="text-accent hover:underline">Browse <?= query_val("SELECT COUNT(*) FROM clis") ?>+ CLIs</a> organized by service.</p>
                  </div>
                </div>
              </div>
              <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <div class="flex items-start gap-3">
                  <span class="text-accent font-bold text-lg mt-0.5">2.</span>
                  <div>
                    <h3 class="font-semibold text-white mb-1">Guide your agent through the CLI</h3>
                    <p class="text-zinc-400 text-sm">Have a conversation. Tell your agent what you want done. Let it try the CLI, fail, run <code class="text-accent">--help</code>, and figure it out. You're teaching by doing — not by writing documentation.</p>
                  </div>
                </div>
              </div>
              <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <div class="flex items-start gap-3">
                  <span class="text-accent font-bold text-lg mt-0.5">3.</span>
                  <div>
                    <h3 class="font-semibold text-white mb-1">Capture it in a custom skill</h3>
                    <p class="text-zinc-400 text-sm">Once the agent knows how to do it, tell it to remember. It writes a skill with your project IDs, your configs, your workflows. Next time, the agent has a reusable runbook.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CTA -->
          <div class="flex flex-col sm:flex-row items-center gap-4 pt-4">
            <a href="/" class="inline-flex items-center gap-2 bg-accent text-black font-bold px-6 py-3 rounded-lg hover:bg-accent/80 transition-colors text-sm">
              <?= icon('search', 'w-4 h-4') ?> Browse <?= query_val("SELECT COUNT(*) FROM clis") ?>+ CLIs
            </a>
          </div>
        </div>
      </div>
    </article>
    <?php
    echo html_wrap('Why CLI tools matter for AI agents — ' . SITE_NAME, ob_get_clean(), 'Why CLI tools are a practical interface for AI agents: lightweight, discoverable, composable, and easy to teach with skills.');
}
