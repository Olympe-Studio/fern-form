<?php

declare(strict_types=1);

namespace Fern\Form\Admin;

if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\FernFormPlugin;
use Fern\Form\Includes\TemplateLoader;

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

    $c = get_the_content();
    $content = json_decode($c, true);
    $content['submitted_at'] = get_the_date('d/m/Y H:i:s');

    if (is_null($content)) {
      TemplateLoader::render('submission_failed', [
        'post' => $post,
      ]);
      return;
    }

    TemplateLoader::render('submission', [
      'post' => $post,
      'content' => $content,
    ]);
  }
}
