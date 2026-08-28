<?php

declare(strict_types=1);

/**
 * Minimal WordPress stubs — just enough to boot fern-form.php and drive
 * FormSubmission::store() and FernFormPlugin::cleanupOldSubmissions() from
 * the CLI. No framework: the harness in run.php is the only consumer.
 */

define('ABSPATH', '/tmp/');

$GLOBALS['__filters'] = [];
$GLOBALS['__posts'] = [];
$GLOBALS['__get_posts_calls'] = [];
$GLOBALS['__get_posts_impl'] = static fn(array $args): array => [];
$GLOBALS['__wp_delete_post_impl'] = static fn($id): bool => true;

function add_filter(string $tag, callable $cb, int $prio = 10, int $args = 1): bool {
  $GLOBALS['__filters'][$tag][] = $cb;
  return true;
}

function apply_filters(string $tag, $value, ...$rest) {
  foreach ($GLOBALS['__filters'][$tag] ?? [] as $cb) {
    $value = $cb($value, ...$rest);
  }
  return $value;
}

function add_action(string $tag, callable $cb, int $prio = 10, int $args = 1): bool { return true; }
function do_action(string $tag, ...$args): void {}
function register_deactivation_hook(string $file, callable $cb): void {}
function wp_next_scheduled(string $hook) { return time(); }
function wp_schedule_event(...$args): bool { return true; }
function is_admin(): bool { return false; }
function __(string $s, ?string $d = null): string { return $s; }

function sanitize_title(string $s): string {
  return trim(strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s)), '-');
}
function sanitize_text_field($s) { return is_string($s) ? trim($s) : $s; }
function sanitize_textarea_field($s) { return is_string($s) ? trim($s) : $s; }
function is_email(string $s) { return filter_var($s, FILTER_VALIDATE_EMAIL) !== false ? $s : false; }
function sanitize_email(string $s): string { return $s; }
function wp_json_encode($data, int $flags = 0) { return json_encode($data, $flags); }
function current_time(string $format): string { return date($format); }
function wp_slash($value) { return $value; }
function get_the_title($id): string { return ''; }

class WP_Error {
  public function __construct(public string $code = '', public string $message = '') {}
}
function is_wp_error($thing): bool { return $thing instanceof WP_Error; }

function term_exists(string $slug, string $tax) { return null; }
function wp_insert_term(string $name, string $tax, array $args = []): array { return ['term_id' => 1]; }

function wp_insert_post(array $data, bool $wp_error = false): int {
  static $id = 100;
  $id++;
  $GLOBALS['__posts'][$id] = $data;
  return $id;
}

function wp_get_post_terms($post_id, string $tax) {
  if (!is_int($post_id) || $post_id <= 0) {
    return new WP_Error('invalid_post', 'Invalid post id');
  }
  return [];
}

function wp_set_post_terms($post_id, array $terms, string $tax): array { return $terms; }
function get_post($id) { return null; }

function get_posts(array $args): array {
  $GLOBALS['__get_posts_calls'][] = $args;
  if (count($GLOBALS['__get_posts_calls']) > 3) {
    // A stand-in for "forever": three identical batches with zero deletions
    // means the loop has no exit.
    throw new RuntimeException('cleanup re-queried the same batch more than 3 times — it would loop forever');
  }
  return ($GLOBALS['__get_posts_impl'])($args);
}

function wp_delete_post($id, bool $force = false) {
  return ($GLOBALS['__wp_delete_post_impl'])($id);
}
