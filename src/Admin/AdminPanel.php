<?php

declare(strict_types=1);

namespace Fern\Form\Admin;

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
   * Display the submission content.
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

    foreach ($content as $key => $value) {
      echo '<div style="padding-bottom: 0.4rem; font-size: 0.9rem;">';
      echo "<strong>{$key}</strong>: {$value}<br>";
      echo '</div>';
    }

    echo '</pre>';
  }
}
