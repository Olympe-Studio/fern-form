<?php

declare(strict_types=1);

namespace Fern\Form\Includes;

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
   * Store the form submission.
   *
   * @return void
   */
  public function store(): void {
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
      $postData = [
        'post_type' => FernFormPlugin::POST_TYPE_NAME,
        'post_title' => $title,
        'post_content' => wp_json_encode($submission),
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
