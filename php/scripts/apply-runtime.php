#!/usr/bin/env php
<?php

declare(strict_types=1);

define('DB_PATH', getenv('CLIS_DB_PATH') ?: dirname(__DIR__, 2) . '/data/clis.sqlite');
define('SITE_NAME', 'CLIs.dev');
define('RUNTIME_SCHEMA_VERSION', '2026-03-08.3');

require dirname(__DIR__) . '/lib/runtime.php';

apply_runtime_schema();

fwrite(STDOUT, "runtime schema applied: " . RUNTIME_SCHEMA_VERSION . PHP_EOL);
