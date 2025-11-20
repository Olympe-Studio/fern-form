<?php

declare(strict_types=1);

namespace Fern\Form\Admin;

if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\FernFormPlugin;
use Fern\Form\Includes\TemplateLoader;
use Fern\Form\Includes\FormSubmission;

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
    add_action('admin_head', [self::class, 'addAdminStyles']);
    add_filter('fern:form:submission_item_value', [self::class, 'formatLanguageCode'], 10, 3);
  }

  /**
   * Format language codes to full names.
   *
   * @param string $value
   * @param string $key
   * @param string $fullKey
   * @return string
   */
  public static function formatLanguageCode(string $value, string $key, string $fullKey): string {
    if (strpos(strtolower($key), 'lang') !== false && strlen($value) === 2) {
      if (class_exists('Locale')) {
        $displayLanguage = \Locale::getDisplayLanguage($value, get_locale());
        if ($displayLanguage) {
          return ucwords($displayLanguage);
        }
      }
      
      // Fallback for common codes if Locale class is missing or fails
      $languages = [
        'en' => __('English', 'default'),
        'fr' => __('French', 'default'),
        'es' => __('Spanish', 'default'),
        'de' => __('German', 'default'),
        'it' => __('Italian', 'default'),
        'pt' => __('Portuguese', 'default'),
        'nl' => __('Dutch', 'default'),
        'ru' => __('Russian', 'default'),
        'zh' => __('Chinese', 'default'),
        'ja' => __('Japanese', 'default'),
      ];

      if (isset($languages[strtolower($value)])) {
        return $languages[strtolower($value)];
      }
    }

    return $value;
  }

  /**
   * Add admin styles for the submission view.
   *
   * @return void
   */
  public static function addAdminStyles(): void {
    if (defined('FERN_FORM_ASSETS') && FERN_FORM_ASSETS === false) {
      return;
    }

    $post = get_post();
    if (!$post || $post->post_type !== FernFormPlugin::POST_TYPE_NAME) {
      return;
    }

    echo <<<HTML
      <style>
        .submission-wrapper {
          font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
          font-size: 14px;
          line-height: 1.5;
          margin: 20px 0;
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 0.5rem;
          padding: 1.5rem;
          box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
          color: #0f172a;
          max-width: 800px;
        }

        .content-row {
          display: flex;
          align-items: baseline;
          padding: 0.375rem 0;
          border-bottom: 1px solid #f1f5f9;
        }

        .content-row:last-child {
          border-bottom: none;
        }

        /* Handle nesting indentation */
        .content-row.indent-0 { margin-left: 0; }
        .content-row.indent-1 { margin-left: 1.5rem; border-left: 2px solid #e2e8f0; padding-left: 0.75rem; }
        .content-row.indent-2 { margin-left: 3rem; border-left: 2px solid #e2e8f0; padding-left: 0.75rem; }
        .content-row.indent-3 { margin-left: 4.5rem; border-left: 2px solid #e2e8f0; padding-left: 0.75rem; }
        .content-row.indent-4 { margin-left: 6rem; border-left: 2px solid #e2e8f0; padding-left: 0.75rem; }

        .key {
          color: #64748b;
          font-weight: 500;
          margin-right: 0.75rem;
          min-width: 120px;
          flex-shrink: 0;
          font-size: 0.875rem;
          text-transform: capitalize;
        }

        .value {
          color: #0f172a;
          font-weight: 400;
          word-break: break-word;
        }

        .url-value {
          color: #2563eb;
          text-decoration: none;
          font-weight: 500;
        }

        .url-value:hover {
          text-decoration: underline;
          color: #1d4ed8;
        }

        .long-text {
          white-space: pre-wrap;
          background: #f8fafc;
          padding: 0.75rem;
          margin-top: 0.25rem;
          border-radius: 0.375rem;
          border: 1px solid #e2e8f0;
          color: #334155;
          font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
          font-size: 0.875rem;
          width: 100%;
        }

        /* Adjust layout for long text or nested items to wrap properly */
        .content-row:has(.long-text),
        .content-row:has(.content-row) {
          flex-direction: column;
          align-items: flex-start;
        }
        
        .content-row:has(.long-text) .key {
          margin-bottom: 0.25rem;
        }

        /* Nested Content Styling */
        .nested-content {
          width: 100%;
          margin-top: 0.5rem;
          padding-left: 1rem;
          border-left: 2px solid #e2e8f0;
        }

        .section-title {
          font-size: 1rem;
          color: #0f172a;
          margin-bottom: 0.5rem;
          display: block;
          width: 100%;
          border-bottom: 1px solid #f1f5f9;
          padding-bottom: 0.25rem;
        }

        .content-row:has(.nested-content) {
          flex-direction: column;
          align-items: flex-start;
          padding-top: 1rem;
        }
        
        /* Remove default indentation for nested wrapper to avoid double indent */
        .content-row:has(.nested-content) > .nested-content > .content-row {
            margin-left: 0 !important;
            border-left: none !important;
            padding-left: 0 !important;
        }
      </style>
    HTML;
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

    $c = $post->post_content;
    $content = FormSubmission::decodeContent($c);

    if (is_null($content)) {
      TemplateLoader::render('submission_failed', [
        'post' => $post,
      ]);
      return;
    }

    $content['Submission Date'] = get_the_date('l jS, Y \a\t H\hi\m\i\ns\s');

    TemplateLoader::render('submission', [
      'post' => $post,
      'content' => $content,
    ]);
  }
}
