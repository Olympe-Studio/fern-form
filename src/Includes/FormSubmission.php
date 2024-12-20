<?php

declare(strict_types=1);

namespace Fern\Form\Includes;

if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\Admin\Notifications;
use Fern\Form\FernFormPlugin;

final class FormSubmission {
  private string $formName;
  private array $submission;

  /**
   * @param string $formName
   * @param array<string, mixed> $submission
   */
  public function __construct(string $formName, array $submission) {
    $this->formName = $formName;
    $this->submission = $submission;
  }


  /**
   * Sanitize submission data recursively.
   *
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  private function sanitizeSubmissionData(array $data): array {
    $sanitized = [];

    foreach ($data as $key => $value) {
      $sanitizedKey = sanitize_key($key);

      if (is_bool($value)) {
        $sanitized[$sanitizedKey] = (bool)$value;
        continue;
      }

      if (is_string($value)) {
        if (strpos($value, '@') !== false && filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $sanitized[$sanitizedKey] = sanitize_email($value);
          continue;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace('"', '\"', $value);

        if (str_word_count($value) > 20) {
          $sanitized[$sanitizedKey] = sanitize_textarea_field($value);
          continue;
        }

        $sanitized[$sanitizedKey] = sanitize_text_field($value);
        continue;
      }

      $sanitized[$sanitizedKey] = $value;
    }

    return $sanitized;
  }

  /**
   * Store the form submission.
   *
   * @return void
   * @throws \Exception
   */
  public function store(): void {
    /**
     * Allow aborting the submission.
     *
     * @param bool $shouldAbort
     * @param string $formName
     * @param array<string, mixed> $submission
     *
     * @return bool
     */
    $shouldAbort = apply_filters('fern:form:submission_should_abort', false, $this->formName, $this->submission);
    if ($shouldAbort) {
      return;
    }

    /**
     * Allow filtering of the submission data.
     *
     * @param array<string, mixed> $submission
     *
     * @return array<string, mixed>
     */
    $submission = apply_filters('fern:form:submission_data', $this->submission);
    $slug = sanitize_title($this->formName);

    $config = FernFormPlugin::getInstance()->getConfig();
    $isWpError = false;
    $retentionDays = $config->getRetentionDays();

    if ($retentionDays < 0 || is_null($retentionDays)) {
      $postId = null;
    } else {
      $defaultTitle = sprintf(
        '%s at %s',
        $this->formName,
        current_time('d/m/Y H:i:s')
      );

      /**
       * Allow filtering of the submission title.
       *
       * @param string $defaultTitle
       * @param string $formName
       * @param array<string, mixed> $submission
       *
       * @return string
       */
      $title = apply_filters('fern:form:submission_title', $defaultTitle, $this->formName, $submission);
      $sanitizedSubmission = $this->sanitizeSubmissionData($submission);

      $jsonContent = wp_json_encode($sanitizedSubmission, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception(sprintf(
          'Form submission JSON encoding error: %s. Data: %s',
          json_last_error_msg(),
          print_r($sanitizedSubmission, true)
        ));
      }

      $postData = [
        'post_type' => FernFormPlugin::POST_TYPE_NAME,
        'post_title' => $title,
        'post_content' => $jsonContent,
        'post_status' => 'publish',
        'meta_input' => [
          Notifications::READ_STATUS_META_KEY => 'unread'
        ],
        'tax_input' => [
          FernFormPlugin::TAXONOMY_NAME => [$slug]
        ]
      ];

      $postId = wp_insert_post($postData);
      $isWpError = is_wp_error($postId);
    }

    if (!$isWpError) {
      /**
       * When the submission is successfully stored.
       *
       * @param int $postId
       * @param string $formName
       * @param array<string, mixed> $submission
       */
      do_action('fern:form:submission_stored', $postId, $slug, $submission);
    }

    if ($isWpError) {
      /**
       * Allow fallback to custom error handling.
       *
       * @param \WP_Error $error
       * @param string $formName
       * @param array<string, mixed> $submission
       */
      do_action('fern:form:submission_error', $isWpError, $slug, $submission);
    }
  }
}
