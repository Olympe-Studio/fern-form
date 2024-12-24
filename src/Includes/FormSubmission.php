<?php

declare(strict_types=1);

namespace Fern\Form\Includes;

if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\Admin\Notifications;
use Fern\Form\FernFormPlugin;

final class FormSubmission {
  private const MIN_LONG_TEXT_WORDS = 20;
  private const READ_STATUS = 'unread';

  /** @var string */
  private string $formName;
  /** @var array<string, mixed> */
  private array $submission;
  /** @var int|null */
  private ?int $id = null;

  /**
   * @param string $formName
   * @param array<string, mixed> $submission
   * @param int|null $id
   */
  public function __construct(string $formName, array $submission, ?int $id = null) {
    if (empty($formName)) {
      throw new \RuntimeException('Form name cannot be empty');
    }

    if (empty($submission)) {
      throw new \RuntimeException('Submission cannot be empty');
    }

    $this->formName = $formName;
    $this->submission = $submission;
    $this->id = $id;
  }

  /**
   * Get FormSubmission by its WordPress ID
   *
   * @param int $id
   *
   * @return self|null
   */
  public static function getById(int $id): ?self {
    if ($id <= 0) {
      return null;
    }

    $post = get_post($id);
    if (!$post || $post->post_type !== FernFormPlugin::POST_TYPE_NAME) {
      return null;
    }

    $terms = wp_get_post_terms($id, FernFormPlugin::TAXONOMY_NAME);
    if (is_wp_error($terms)) {
      return null;
    }

    $formName = $terms[0]->name ?? '';
    $submission = json_decode($post->post_content, true) ?? [];

    return new self($formName, $submission, $id);
  }

  /**
   * Get the WordPress ID of the form submission
   *
   * @return int|null
   */
  public function getId(): ?int {
    return $this->id;
  }

  /**
   * Get the form submission data
   *
   * @return array<string, mixed>
   */
  public function getData(): array {
    return $this->submission;
  }

  /**
   * Get the form name
   *
   * @return string
   */
  public function getFormName(): string {
    return $this->formName;
  }

  /**
   * Delete the form submission
   *
   * @return void
   *
   * @throws \RuntimeException
   */
  public function delete(): void {
    if (!$this->id) {
      throw new \RuntimeException('Cannot delete submission without ID');
    }
    /**
     * Allow custom logic before deleting the submission.
     *
     * @param int $id
     * @param string $formName
     */
    do_action('fern:form:before_delete', $this->id, $this->formName);

    /**
     * Allow aborting the submission when deleting.
     *
     * @param bool $shouldAbort
     * @param string $formName
     * @param array<string, mixed> $submission
     *
     * @return bool
     */
    $shouldAbort = apply_filters('fern:form:delete_submission_should_abort', false, $this->formName, $this->submission);
    if ($shouldAbort) {
      return;
    }

    $deleted = wp_delete_post($this->id, true);
    if (!$deleted) {
      throw new \RuntimeException(sprintf(
        'Failed to delete submission: %s',
        print_r($this->id, true)
      ));
    }

    /**
     * When the submission is successfully deleted.
     *
     * @param int $id
     * @param string $formName
     */
    do_action('fern:form:after_delete', $this->id, $this->formName);
  }

  /**
   * Update the form submission
   *
   * @param array<string, mixed> $data
   *
   * @return void
   *
   * @throws \RuntimeException
   */
  public function update(array $data): void {
    if (!$this->id) {
      throw new \RuntimeException('Cannot update submission without ID');
    }

    /**
     * Allow aborting the submission when updating.
     *
     * @param bool $shouldAbort
     * @param string $formName
     * @param array<string, mixed> $submission
     *
     * @return bool
     */
    $shouldAbort = apply_filters('fern:form:update_submission_should_abort', false, $this->formName, $this->submission);
    if ($shouldAbort) {
      return;
    }

    /**
     * Allow filtering of the submission data when updating.
     *
     * @param array<string, mixed> $submission
     *
     * @return array<string, mixed>
     */
    $submission = apply_filters('fern:form:update_submission_data', $data);
    $sanitizedData = $this->sanitizeSubmissionData($submission);
    $jsonContent = $this->encodeSubmission($sanitizedData);
    $currentTitle = get_the_title($this->id);

    /**
     * Allow filtering of the submission title when updating.
     *
     * @param string $currentTitle
     * @param string $formName
     * @param array<string, mixed> $submission
     *
     * @return string
     */
    $title = apply_filters('fern:form:update_submission_title', $currentTitle, $this->formName, $submission);

    $updated = wp_update_post([
      'ID' => $this->id,
      'post_title' => $title,
      'post_content' => $jsonContent
    ]);

    if (!$updated) {
      /**
       * Allow fallback to custom error handling when updating.
       *
       * @param int $id
       * @param string $formName
       * @param array<string, mixed> $submission
       */
      do_action('fern:form:update_submission_error', $this->id, $this->formName, $submission);
      return;
    }

    /**
     * When the submission is successfully updated.
     *
     * @param int $id
     * @param string $formName
     * @param array<string, mixed> $submission
     */
    do_action('fern:form:submission_updated', $this->id, $this->formName, $submission);
    $this->submission = $sanitizedData;
  }

  /**
   * Encode the submission data to JSON
   *
   * @param array<string, mixed> $data
   *
   * @return string
   *
   * @throws \RuntimeException
   */
  private function encodeSubmission(array $data): string {
    $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \RuntimeException(sprintf(
        'JSON encoding error: %s. Data: %s',
        json_last_error_msg(),
        print_r($data, true)
      ));
    }

    return $json;
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

        if (str_word_count($value) > self::MIN_LONG_TEXT_WORDS) {
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
   * @throws \RuntimeException
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
      $jsonContent = $this->encodeSubmission($sanitizedSubmission);

      $postData = [
        'post_type' => FernFormPlugin::POST_TYPE_NAME,
        'post_title' => $title,
        'post_content' => $jsonContent,
        'post_status' => 'publish',
        'meta_input' => [
          Notifications::READ_STATUS_META_KEY => self::READ_STATUS
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
      $this->id = $postId;
      $this->submission = $submission;
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
