<?php

declare(strict_types=1);

/**
 * Standalone regression harness for fern-form. No framework.
 *
 *   php tests/run.php            run every scenario, each in its own process
 *   php tests/run.php <name>     run one scenario in this process
 *
 * Each scenario needs its own process: the plugin is a singleton whose
 * configuration timing is precisely what several of these bugs are about.
 * All five are red on 2.0.2 (except cleanup-disabled) and green on 2.0.3.
 */

const SCENARIOS = [
  'api-loads',
  'late-partial-filter',
  'early-partial-filter',
  'store-with-retention-disabled',
  'cleanup-terminates-and-uses-gmt',
  'cleanup-disabled',
  'cleanup-overflow-guard',
  'admin-view-renders',
];

$root = dirname(__DIR__);

if ($argc < 2) {
  $failures = 0;
  foreach (SCENARIOS as $name) {
    $out = [];
    $code = 0;
    // short_open_tag pinned to Off — the php.ini-production default the
    // admin views must survive.
    exec(sprintf('%s -d short_open_tag=0 %s %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg(__FILE__), escapeshellarg($name)), $out, $code);
    $text = implode("\n", $out);
    echo $text, "\n";
    if ($code !== 0) {
      $failures++;
      if (!str_contains($text, 'FAIL ')) {
        // A compile-time fatal (e.g. the 2.0.2 namespace error) kills the
        // child before it can label itself.
        echo "FAIL {$name}: process died (exit {$code}) — see fatal above\n";
      }
    }
  }
  echo $failures === 0 ? "\nALL PASS\n" : "\n{$failures} FAILED\n";
  exit($failures === 0 ? 0 : 1);
}

$scenario = $argv[1];

function pass(string $name): never {
  echo "PASS {$name}\n";
  exit(0);
}

function fail(string $name, string $reason): never {
  echo "FAIL {$name}: {$reason}\n";
  exit(1);
}

require __DIR__ . '/wp-stubs.php';

$boot = static function () use ($root): void {
  require $root . '/fern-form.php';
};

switch ($scenario) {
  /*
   * Defect 1 — src/API/FernForm.php had its ABSPATH guard between declare()
   * and namespace, a fatal parse error on every PHP 8. The public API class
   * was unloadable, so every fern_form_store() call fataled.
   */
  case 'api-loads':
    $boot();
    try {
      if (!class_exists(\Fern\Form\API\FernForm::class)) {
        fail($scenario, 'API class missing after boot');
      }
    } catch (\ParseError $e) {
      fail($scenario, 'API class does not parse: ' . $e->getMessage());
    }
    if (!function_exists('fern_form_store')) {
      fail($scenario, 'fern_form_store() not defined');
    }
    pass($scenario);

  /*
   * Defect 3 — the config was frozen in the plugin constructor, at plugin
   * load time. A fern:form:config filter added later (a theme loads after
   * plugins) was silently ignored: retention stayed at 7 days.
   * Defect 4 — the filter is public, so returning only the key you care
   * about must not fatal; missing keys fall back to the defaults.
   */
  case 'late-partial-filter':
    $boot();
    add_filter('fern:form:config', static fn(array $c): array => ['retention_days' => -1]);
    try {
      $config = \Fern\Form\FernFormPlugin::getInstance()->getConfig();
    } catch (\Throwable $e) {
      fail($scenario, 'partial filter return fatals: ' . $e->getMessage());
    }
    if ($config->getRetentionDays() !== -1) {
      fail($scenario, 'filter added after plugin load is ignored — retention is ' . $config->getRetentionDays());
    }
    if ($config->getFormCapabilities() !== ['create' => 'edit_posts', 'read' => 'read', 'delete' => 'delete_posts']) {
      fail($scenario, 'missing capabilities did not fall back to defaults');
    }
    pass($scenario);

  /*
   * Defect 4, at the timing 2.0.2 did honour — a partial return from a
   * filter registered before plugin load assigned null to a typed array
   * property in Config's constructor: fatal at boot.
   */
  case 'early-partial-filter':
    add_filter('fern:form:config', static fn(array $c): array => ['retention_days' => -1]);
    try {
      $boot();
      $config = \Fern\Form\FernFormPlugin::getInstance()->getConfig();
    } catch (\Throwable $e) {
      fail($scenario, 'boot fatals on partial config: ' . $e->getMessage());
    }
    if ($config->getRetentionDays() !== -1) {
      fail($scenario, 'retention is ' . $config->getRetentionDays());
    }
    pass($scenario);

  /*
   * Defect 2 — retention_days < 0 (the only value that disables the purge)
   * gated store() too: $postId stayed null, fell into the success branch,
   * and wp_get_post_terms(null)'s WP_Error hit in_array() — a TypeError.
   * Disabling the cleanup must not break storage.
   */
  case 'store-with-retention-disabled':
    add_filter('fern:form:config', static fn(array $c): array => ['retention_days' => -1] + $c);
    $boot();
    try {
      $submission = new \Fern\Form\Includes\FormSubmission('devis', [
        'societe' => 'Aciéries de l\'Est',
        'email' => 'achats@example.com',
      ]);
      $id = $submission->store();
    } catch (\Throwable $e) {
      fail($scenario, get_class($e) . ': ' . $e->getMessage());
    }
    if (!is_int($id)) {
      fail($scenario, 'store() returned ' . var_export($id, true) . ' instead of a post id');
    }
    $post = $GLOBALS['__posts'][$id] ?? null;
    if ($post === null || $post['post_type'] !== \Fern\Form\FernFormPlugin::POST_TYPE_NAME) {
      fail($scenario, 'no submission post was inserted');
    }
    $decoded = json_decode((string) $post['post_content'], true);
    if (($decoded['email'] ?? null) !== 'achats@example.com') {
      fail($scenario, 'stored payload does not round-trip');
    }
    pass($scenario);

  /*
   * Defect 5 — the cleanup do/while re-queried the same batch forever when
   * deletions failed, and compared a gmdate() cutoff against post_date
   * (site-local time) instead of post_date_gmt.
   */
  case 'cleanup-terminates-and-uses-gmt':
    $boot();
    $GLOBALS['__get_posts_impl'] = static fn(array $args): array => [1, 2, 3];
    $GLOBALS['__wp_delete_post_impl'] = static fn($id): bool => false;
    try {
      \Fern\Form\FernFormPlugin::getInstance()->cleanupOldSubmissions(3);
    } catch (\RuntimeException $e) {
      fail($scenario, $e->getMessage());
    }
    $query = $GLOBALS['__get_posts_calls'][0] ?? null;
    if ($query === null) {
      fail($scenario, 'cleanup never queried');
    }
    if (($query['date_query']['column'] ?? null) !== 'post_date_gmt') {
      fail($scenario, 'cutoff compared against ' . ($query['date_query']['column'] ?? 'post_date (default)') . ' — gmdate() cutoff needs post_date_gmt');
    }
    pass($scenario);

  /* Retention < 0 disables the cleanup entirely: no query, no deletion. */
  case 'cleanup-disabled':
    add_filter('fern:form:config', static fn(array $c): array => ['retention_days' => -1] + $c);
    $boot();
    \Fern\Form\FernFormPlugin::getInstance()->cleanupOldSubmissions();
    if ($GLOBALS['__get_posts_calls'] !== []) {
      fail($scenario, 'cleanup queried posts despite retention_days = -1');
    }
    pass($scenario);

  /*
   * An absurd retention (strtotime overflow makes the cutoff land in the
   * future) must mean "keep everything", never "delete everything".
   */
  case 'cleanup-overflow-guard':
    add_filter('fern:form:config', static fn(array $c): array => ['retention_days' => PHP_INT_MAX] + $c);
    $boot();
    $GLOBALS['__get_posts_impl'] = static fn(array $args): array => [1, 2, 3];
    \Fern\Form\FernFormPlugin::getInstance()->cleanupOldSubmissions();
    if ($GLOBALS['__get_posts_calls'] !== []) {
      fail($scenario, 'overflowed cutoff still queried posts — this deletes the whole store');
    }
    pass($scenario);

  /*
   * Defect 6 — every admin view was written with `<?` short open tags. With
   * short_open_tag=Off (the php.ini-production default) nothing executes:
   * the admin screen shows raw PHP source instead of the submission.
   */
  case 'admin-view-renders':
    $boot();
    ob_start();
    \Fern\Form\Includes\TemplateLoader::render('submission', [
      'post' => null,
      'content' => [
        'societe' => 'Acieries de l\'Est',
        'email' => 'achats@example.com',
        'lignes' => ['lots' => 3],
      ],
    ]);
    $html = (string) ob_get_clean();
    if (str_contains($html, '<?')) {
      fail($scenario, 'raw PHP leaks into the admin output — short open tags with short_open_tag=Off');
    }
    foreach (['achats@example.com', 'Acieries', 'Lots'] as $needle) {
      if (!str_contains($html, $needle)) {
        fail($scenario, "rendered view is missing '{$needle}'");
      }
    }
    pass($scenario);

  default:
    fail($scenario, 'unknown scenario');
}
