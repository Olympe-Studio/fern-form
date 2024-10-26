<?php

declare(strict_types=1);

namespace Fern\Form\Admin;

if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\FernFormPlugin;

class AdminPanel {
  /**
   * Boot up the admin panel
   *
   * @return void
   */
  public static function boot(): void {
    if (is_admin()) {
      Notifications::boot(FernFormPlugin::POST_TYPE_NAME);
    }

    add_action('edit_form_after_title', [self::class, 'displaySubmission']);
  }

  /**
   * Display the submission content recursively.
   *
   * @return void
   */
  public static function displaySubmission(): void {
    $post = get_post();
    if ($post->post_type !== FernFormPlugin::POST_TYPE_NAME) {
      return;
    }

    $content = json_decode(get_the_content(), true);

    echo '<pre style="padding-top: 1rem;">';
    self::renderContentRecursively($content);
    echo '</pre>';
  }

  /**
   * Recursively render content with proper indentation.
   *
   * @param array<string, mixed> $content The content to display
   * @param int $depth Current depth level for indentation
   * @param string $parentKey Parent key for nested items
   * @return void
   */
  private static function renderContentRecursively(array $content, int $depth = 0, string $parentKey = ''): void {
    $indent = str_repeat('    ', $depth); // 4 spaces per level

    foreach ($content as $key => $value) {
      $displayKey = esc_html($parentKey ? "$parentKey.$key" : $key);

      echo '<div style="padding-bottom: 0.4rem; font-size: 0.9rem;">';
      echo esc_html($indent);

      if (is_array($value)) {
        echo "<strong>{$displayKey}</strong>:";
        echo '</div>';
        self::renderContentRecursively($value, $depth + 1, $displayKey);
      } else {
        // Handle different value types
        if (is_bool($value)) {
          $displayValue = $value ? 'true' : 'false';
        } elseif (is_null($value)) {
          $displayValue = 'null';
        } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
          // Create clickable links for URLs
          $displayValue = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url($value),
            esc_html($value)
          );
        } else {
          $displayValue = esc_html((string) $value);
        }

        echo "<strong>{$displayKey}</strong>: {$displayValue}";
        echo '</div>';
      }
    }
  }
}
