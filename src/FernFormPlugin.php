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
   * @var Config
   */
  private Config $config;

  public const TAXONOMY_NAME = 'fern_form_category';
  public const POST_TYPE_NAME = 'fern_form_submission';
  public const PLUGIN_VERSION = FERN_FORM_VERSION;
  public const PLUGIN_DIR = FERN_FORM_DIR . '/src';

  /**
   * Initialize the plugin.
   */
  private function __construct() {
    $this->boot();
    $this->registerHooks();
  }

  /**
   * Get the plugin configuration.
   *
   * @return Config
   */
  public function getConfig(): Config {
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
   * Initialize the plugin configuration.
   */
  public function boot(): void {
    $defaultConfig = [
      'retention_days' => 90,
      'form_capabilities' => [
        'create' => 'edit_posts',
        'read' => 'read',
        'delete' => 'delete_posts'
      ]
    ];

    /** @var array<string, mixed> $finalConfig */
    add_filter('init', function () use ($defaultConfig) {
      $finalConfig = apply_filters('fern:form:config', $defaultConfig);
      $old = $this->config;
      $this->config = new Config($finalConfig);
    }, 10, 0);

    // default config
    $this->config = new Config($defaultConfig);

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
        ...$this->config->getFormCapabilities()
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
   * @return void
   */
  public function cleanupOldSubmissions(int $batchSize = 100): void {
    $retentionDays = $this->config->getRetentionDays();

    // Pass if retention days is not set
    if ($retentionDays < 0 || is_null($retentionDays)) {
      return;
    }

    $currentGMT = time();
    $cutoffTimestamp = strtotime("-{$retentionDays} days", $currentGMT);
    $cutoffDate = gmdate('Y-m-d H:i:s', $cutoffTimestamp);

    do {
      $oldSubmissions = get_posts([
        'post_type' => self::POST_TYPE_NAME,
        'date_query' => [
          'before' => $cutoffDate
        ],
        'posts_per_page' => $batchSize,
        'fields' => 'ids'
      ]);

      foreach ($oldSubmissions as $postId) {
        wp_delete_post($postId, true);
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

    $wpdb->delete(
      $wpdb->term_taxonomy,
      ['taxonomy' => self::TAXONOMY_NAME],
      ['%s']
    );

    delete_option(self::POST_TYPE_NAME . '_capabilities');
    delete_option('_transient_' . self::POST_TYPE_NAME . '_capabilities');

    wp_clear_scheduled_hook('fern:form:scheduled_cleanup');
    flush_rewrite_rules();
  }
}