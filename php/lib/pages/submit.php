<?php

function page_submit() {
    ob_start();
    ?>
    <section class="py-12">
      <div class="max-w-2xl mx-auto px-4 text-center">
        <h1 class="text-2xl font-bold text-white mb-4">Submit a CLI</h1>
        <p class="text-zinc-500 mb-8">Know a great CLI that's missing? Submit it via GitHub Issues and we'll review it.</p>
        <a href="<?= GITHUB_REPO ?>/issues/new?template=submit-cli.yml" 
          target="_blank" rel="noopener"
          class="inline-flex items-center gap-2 bg-accent text-black font-bold px-6 py-3 rounded-lg hover:bg-accent/80 transition-colors">
          <?= icon('github', 'w-5 h-5') ?> Open GitHub Issue
        </a>
        <p class="text-zinc-600 text-xs mt-6">Reviewed manually.</p>
      </div>
    </section>
    <?php
    echo html_wrap('Submit a CLI — ' . SITE_NAME, ob_get_clean());
}
