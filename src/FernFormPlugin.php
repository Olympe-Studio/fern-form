<?php

declare(strict_types=1);

namespace Fern\Form;

if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\Admin\AdminPanel;

final class FernFormPlugin {
  /**
   * @var ?self
   */
  private static ?self $instance = null;

  /**
   * Resolved on first access, never at plugin load time. See getConfig().
   *
   * @var ?Config
   */
  private ?Config $config = null;

  public const TAXONOMY_NAME = 'fern_form_category';
  public const POST_TYPE_NAME = 'fern_form_submission';
  public const PLUGIN_VERSION = FERN_FORM_VERSION;
  public const PLUGIN_DIR = FERN_FORM_DIR . '/src';

  /**
   * Initialize the plugin.
   */
  private function __construct() {
    $this->registerHooks();
  }

  /**
   * Get the plugin configuration.
   *
   * The configuration is resolved on first access, not at plugin load time.
   * This plugin file is required before themes are loaded, so a `fern:form:config`
   * filter added from a theme would never have been seen if the config were
   * frozen in the constructor. Both readers run late enough for that to be safe:
   * capabilities are read on `init` (priority 6) and retention only when the
   * daily cron event fires.
   *
   * @return Config
   */
  public function getConfig(): Config {
    if ($this->config === null) {
      $defaultConfig = [
        'retention_days' => 7,
        'form_capabilities' => [
          'create' => 'edit_posts',
          'read' => 'read',
          'delete' => 'delete_posts'
        ]
      ];

      /** @var array<string, mixed> $finalConfig */
      $finalConfig = apply_filters('fern:form:config', $defaultConfig);
      $this->config = Config::fromArray($finalConfig, $defaultConfig);
    }

    return $this->config;
  }

  /**
   * Get the singleton instance of the plugin.
   *
   * @return self
   */
  public static function getInstance(): self {
    if (self::$instance === null) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * Boot the admin surface. Hooked on `plugins_loaded`.
   */
  public function boot(): void {
    if (is_admin()) {
      AdminPanel::boot();
    }
  }

  /**
   * Register the plugin hooks.
   */
  private function registerHooks(): void {
    add_action('init', [$this, 'registerTaxonomy'], 5);
    add_action('init', [$this, 'registerPostType'], 6);
    add_action('admin_init', [$this, 'setupAdminRestrictions']);
    add_action('fern:form:scheduled_cleanup', [$this, 'cleanupOldSubmissions']);

    /*
     * Schedule the cleanup event if it's not already scheduled.
     */
    if (!wp_next_scheduled('fern:form:scheduled_cleanup')) {
      wp_schedule_event(time(), 'daily', 'fern:form:scheduled_cleanup');
    }
  }

  /**
   * Register the form submission post type.
   */
  public function registerPostType(): void {
    register_post_type(self::POST_TYPE_NAME, [
      'labels' => [
        'name' => __('Form Entries', 'fern-form'),
        'singular_name' => __('Form Entry', 'fern-form'),
        'menu_name' => __('Form Entries', 'fern-form'),
        'all_items' => __('All Form Entries', 'fern-form'),
        'view_item' => __('View Form Entry', 'fern-form'),
        'search_items' => __('Search Form Entries', 'fern-form'),
        'not_found' => __('No form entries found', 'fern-form'),
        'not_found_in_trash' => __('No form entries found in trash', 'fern-form'),
        'name_admin_bar' => __('Form Entry', 'fern-form'),
      ],
      'public' => false,
      'show_ui' => true,
      'show_in_menu' => true,
      'capability_type' => 'post',
      'icon' => 'feedback',
      'capabilities' => [
        'create_posts' => 'do_not_allow',
        ...$this->getConfig()->getFormCapabilities()
      ],
      'supports' => ['title', 'editor'],
      'map_meta_cap' => true,
      'show_in_rest' => false,
      'taxonomies' => [self::TAXONOMY_NAME]
    ]);
  }

  /**
   * Register the form category taxonomy.
   */
  public function registerTaxonomy(): void {
    register_taxonomy(
      self::TAXONOMY_NAME,
      self::POST_TYPE_NAME,
      [
        'labels' => [
          'name' => __('Form Categories', 'fern-form'),
          'singular_name' => __('Form Category', 'fern-form'),
          'menu_name' => __('Form Categories', 'fern-form'),
          'all_items' => __('All Form Types', 'fern-form'),
          'edit_item' => __('Edit Form Type', 'fern-form'),
          'view_item' => __('View Form Type', 'fern-form'),
          'update_item' => __('Update Form Type', 'fern-form'),
          'add_new_item' => __('Add New Form Type', 'fern-form'),
          'new_item_name' => __('New Form Type Name', 'fern-form'),
          'search_items' => __('Search Form Types', 'fern-form')
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_admin_column' => true,
        'hierarchical' => false,
        'query_var' => true,
        'rewrite' => false,
        'show_in_rest' => false,
        'meta_box_cb' => false,
        'capabilities' => [
          'manage_terms' => 'manage_categories',
          'edit_terms' => 'manage_categories',
          'delete_terms' => 'manage_categories',
          'assign_terms' => 'edit_posts'
        ]
      ]
    );
  }

  /**
   * Setup the admin restrictions.
   */
  public function setupAdminRestrictions(): void {
    add_filter('post_row_actions', function (array $actions, \WP_Post $post): array {
      if ($post->post_type === self::POST_TYPE_NAME) {
        unset($actions['edit'], $actions['inline hide-if-no-js']);
        return $actions;
      }
      return $actions;
    }, 10, 2);

    add_action('admin_head-post.php', function (): void {
      global $post;
      if ($post->post_type === self::POST_TYPE_NAME) {
        remove_post_type_support(self::POST_TYPE_NAME, 'editor');
        add_filter('enter_title_here', fn() => 'Form submission (read-only)');
      }
    }, 10, 0);

    // Prevent manual term creation in admin
    if ($this->isFormSubmissionAdmin()) {
      add_filter('map_meta_cap', function (array $caps, string $cap): array {
        if (in_array($cap, ['edit_terms', 'delete_terms', 'manage_terms'], true)) {
          return ['do_not_allow'];
        }
        return $caps;
      }, 10, 2);
    }
  }

  /**
   * Check if the current admin page is for the form category taxonomy.
   *
   * @return bool
   */
  private function isFormSubmissionAdmin(): bool {
    global $pagenow, $taxnow;
    return is_admin() &&
      ($pagenow === 'edit-tags.php' || $pagenow === 'term.php') &&
      $taxnow === self::TAXONOMY_NAME;
  }

  /**
   * Cleanup old form submissions.
   *
   * A negative `retention_days` disables the cleanup entirely and keeps every
   * submission. Zero deletes everything older than the moment the cron runs.
   *
   * @return void
   */
  public function cleanupOldSubmissions(int $batchSize = 100): void {
    $retentionDays = $this->getConfig()->getRetentionDays();

    // A negative retention disables the cleanup: keep every submission.
    if ($retentionDays < 0) {
      return;
    }

    /*
     * A cutoff in the future can only be integer overflow inside strtotime()
     * (days × 86400 past PHP_INT_MAX wraps positive), and "everything before
     * a future date" is everything. Treat both as "keep it all" — an absurd
     * retention must never become a full purge.
     */
    $cutoffTimestamp = strtotime("-{$retentionDays} days", time());
    if ($cutoffTimestamp === false || $cutoffTimestamp > time()) {
      return;
    }

    $cutoffDate = gmdate('Y-m-d H:i:s', $cutoffTimestamp);

    do {
      $oldSubmissions = get_posts([
        'post_type' => self::POST_TYPE_NAME,
        // Submissions are stored as 'publish'. Stated rather than inherited
        // from get_posts()'s default, so the scope of the deletion is readable.
        'post_status' => 'publish',
        'date_query' => [
          /*
           * The cutoff is built with gmdate(), so it must be compared against
           * the GMT column. Comparing it to `post_date` (site local time) drifts
           * the cutoff by the site's UTC offset.
           */
          'column' => 'post_date_gmt',
          'before' => $cutoffDate
        ],
        'posts_per_page' => $batchSize,
        'fields' => 'ids'
      ]);

      $deleted = 0;
      foreach ($oldSubmissions as $postId) {
        if (wp_delete_post($postId, true)) {
          $deleted++;
        }
      }

      /*
       * Stop when a pass deletes nothing. Without this the loop re-queries the
       * same undeletable batch forever — a hung cron event, not a slow one.
       */
      if ($deleted === 0) {
        return;
      }
    } while (count($oldSubmissions) === $batchSize);
  }

  /**
   * Handle the plugin deactivation.
   */
  public function handleDeactivation(): void {
    if (!defined('FERN_CLEAR_ON_DEACTIVATE') || !FERN_CLEAR_ON_DEACTIVATE) {
      return;
    }

    $this->clearAllData();
  }

  /**
   * Clear all plugin data including posts, terms.
   */
  private function clearAllData(): void {
    global $wpdb;

    $posts = get_posts([
      'post_type' => self::POST_TYPE_NAME,
      'numberposts' => -1,
      'post_status' => 'any',
      'fields' => 'ids'
    ]);

    foreach ($posts as $postId) {
      wp_delete_post($postId, true);
    }

    $terms = get_terms([
      'taxonomy' => self::TAXONOMY_NAME,
      'hide_empty' => false,
      'fields' => 'ids'
    ]);

    if (!is_wp_error($terms)) {
      foreach ($terms as $termId) {
        wp_delete_term($termId, self::TAXONOMY_NAME);
      }
    }



    delete_option(self::POST_TYPE_NAME . '_capabilities');
    delete_option('_transient_' . self::POST_TYPE_NAME . '_capabilities');

    wp_clear_scheduled_hook('fern:form:scheduled_cleanup');
    flush_rewrite_rules();
  }
}
