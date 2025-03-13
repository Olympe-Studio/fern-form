<?php

declare(strict_types=1);

namespace Fern\Form\Includes;

if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\Admin\Notifications;
use Fern\Form\FernFormPlugin;

/**
 * FormSubmission class handles the creation, retrieval, updating, and deletion of form submissions.
 *
 * @since 1.0.0
 */
class FormSubmission {
  /**
   * Minimum number of words for text to be considered long text
   *
   * @var int
   */
  private const MIN_LONG_TEXT_WORDS = 7;

  /**
   * Default read status for new submissions
   *
   * @var string
   */
  private const READ_STATUS = 'unread';

  /**
   * The form name/identifier
   *
   * @var string
   */
  private string $formName;

  /**
   * The submission data
   *
   * @var array<string, mixed>
   */
  private array $submission;

  /**
   * The submission ID
   *
   * @var int|null
   */
  private ?int $id = null;

  /**
   * Constructor
   *
   * @param string $formName The form name/identifier.
   * @param array<string, mixed> $submission The submission data.
   * @param int|null $id The submission ID (optional).
   *
   * @throws \RuntimeException If form name or submission is empty.
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
   * @param int $id The submission ID.
   *
   * @return static|null The FormSubmission object or null if not found.
   */
  public static function getById(int $id): ?static {
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

    return new static($formName, $submission, $id);
  }

  /**
   * Get the WordPress ID of the form submission
   *
   * @return int|null The submission ID or null if not stored yet.
   */
  public function getId(): ?int {
    return $this->id;
  }

  /**
   * Get the form submission data
   *
   * @return array<string, mixed> The submission data.
   */
  public function getData(): array {
    return $this->submission;
  }

  /**
   * Set the form submission data
   *
   * @param array<string, mixed> $data The new submission data.
   *
   * @return void
   */
  public function setData(array $data): void {
    $this->submission = $data;
  }

  /**
   * Get the form name
   *
   * @return string The form name/identifier.
   */
  public function getFormName(): string {
    return $this->formName;
  }

  /**
   * Delete the form submission
   *
   * @return void
   *
   * @throws \RuntimeException If submission has no ID or deletion fails.
   */
  public function delete(): void {
    if (!$this->id) {
      throw new \RuntimeException('Cannot delete submission without ID');
    }

    /**
     * Allow custom logic before deleting the submission.
     *
     * @param int $id The submission ID.
     * @param string $formName The form name/identifier.
     */
    do_action('fern:form:before_delete', $this->id, $this->formName);

    /**
     * Allow aborting the submission deletion.
     *
     * @param bool $shouldAbort Whether to abort the deletion.
     * @param string $formName The form name/identifier.
     * @param array<string, mixed> $submission The submission data.
     *
     * @return bool Whether to abort the deletion.
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
     * Fires after a submission is successfully deleted.
     *
     * @param int $id The deleted submission ID.
     * @param string $formName The form name/identifier.
     */
    do_action('fern:form:after_delete', $this->id, $this->formName);
  }

  /**
   * Update the form submission
   *
   * @param array<string, mixed> $data The new submission data.
   *
   * @return void
   *
   * @throws \RuntimeException If submission has no ID.
   */
  public function update(array $data): void {
    if (!$this->id) {
      throw new \RuntimeException('Cannot update submission without ID');
    }

    /**
     * Allow aborting the submission update.
     *
     * @param bool $shouldAbort Whether to abort the update.
     * @param string $formName The form name/identifier.
     * @param array<string, mixed> $submission The current submission data.
     *
     * @return bool Whether to abort the update.
     */
    $shouldAbort = apply_filters('fern:form:update_submission_should_abort', false, $this->formName, $this->submission);
    if ($shouldAbort) {
      return;
    }

    /**
     * Filter the submission data before updating.
     *
     * @param array<string, mixed> $data The new submission data.
     *
     * @return array<string, mixed> The filtered submission data.
     */
    $submission = apply_filters('fern:form:update_submission_data', $data);
    $sanitizedData = $this->sanitizeSubmissionData($submission);
    $jsonContent = $this->encodeSubmission($sanitizedData);
    $currentTitle = get_the_title($this->id);

    /**
     * Filter the submission title when updating.
     *
     * @param string $currentTitle The current post title.
     * @param string $formName The form name/identifier.
     * @param array<string, mixed> $submission The new submission data.
     *
     * @return string The filtered submission title.
     */
    $title = apply_filters('fern:form:update_submission_title', $currentTitle, $this->formName, $submission);

    $updated = wp_update_post([
      'ID'           => $this->id,
      'post_title'   => $title,
      'post_content' => $jsonContent,
    ]);

    if (!$updated) {
      /**
       * Fires when an update fails.
       *
       * @param int $id The submission ID.
       * @param string $formName The form name/identifier.
       * @param array<string, mixed> $submission The new submission data.
       */
      do_action('fern:form:update_submission_error', $this->id, $this->formName, $submission);
      return;
    }

    /**
     * Fires after a submission is successfully updated.
     *
     * @param int $id The submission ID.
     * @param string $formName The form name/identifier.
     * @param array<string, mixed> $submission The new submission data.
     */
    do_action('fern:form:submission_updated', $this->id, $this->formName, $submission);
    $this->submission = $sanitizedData;
  }

  /**
   * Encode the submission data to JSON
   *
   * @param array<string, mixed> $data The data to encode.
   *
   * @return string The JSON encoded data.
   *
   * @throws \RuntimeException If JSON encoding fails.
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
   * @param array<string, mixed> $data The data to sanitize.
   *
   * @return array<string, mixed> The sanitized data.
   */
  private function sanitizeSubmissionData(array $data): array {
    $sanitized = [];

    foreach ($data as $key => $value) {
      $sanitizedKey = sanitize_key($key);

      if (is_bool($value)) {
        $sanitized[$sanitizedKey] = (bool) $value;
        continue;
      }

      if (is_string($value)) {
        if (strpos($value, '@') !== false && filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $sanitized[$sanitizedKey] = sanitize_email($value);
          continue;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = str_replace('"', '\"', $value);

        /**
         * Determine if a field should be treated as a text area.
         *
         * @param bool $isTextArea Whether the field is a text area.
         * @param string $formName The form name/identifier.
         * @param string $key The original field key.
         * @param string $sanitizedKey The sanitized field key.
         * @param string $value The field value.
         *
         * @return bool Whether the field should be treated as a text area.
         */
        $isTextArea = apply_filters('fern:form:is_text_area', false, $this->formName, $key, $sanitizedKey, $value);

        /**
         * Filter the minimum number of words for text to be considered long text.
         *
         * @param int $minLongTextWords The default minimum word count.
         *
         * @return int The filtered minimum word count.
         */
        $minLongTextWords = apply_filters('fern:form:min_long_text_words', self::MIN_LONG_TEXT_WORDS);

        if ($isTextArea || str_word_count($value) > $minLongTextWords) {
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
   * Set the form submission ID
   *
   * @param int $id The submission ID.
   *
   * @return void
   */
  public function setId(int $id): void {
    $this->id = $id;
  }

  /**
   * Store the form submission.
   *
   * @return int|null The submission ID or null if storage failed.
   *
   * @throws \RuntimeException If JSON encoding fails.
   */
  public function store(): ?int {
    /**
     * Allow aborting the submission storage.
     *
     * @param bool $shouldAbort Whether to abort the storage.
     * @param string $formName The form name/identifier.
     * @param array<string, mixed> $submission The submission data.
     *
     * @return bool Whether to abort the storage.
     */
    $shouldAbort = apply_filters('fern:form:submission_should_abort', false, $this->formName, $this->submission);
    if ($shouldAbort) {
      return null;
    }

    /**
     * Filter the submission data before storage.
     *
     * @param array<string, mixed> $submission The submission data.
     *
     * @return array<string, mixed> The filtered submission data.
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
       * Filter the submission title.
       *
       * @param string $defaultTitle The default title.
       * @param string $formName The form name/identifier.
       * @param array<string, mixed> $submission The submission data.
       *
       * @return string The filtered title.
       */
      $title = apply_filters('fern:form:submission_title', $defaultTitle, $this->formName, $submission);
      $sanitizedSubmission = $this->sanitizeSubmissionData($submission);
      $jsonContent = $this->encodeSubmission($sanitizedSubmission);

      $slug = sanitize_title($this->formName);
      // Ensure the term exists
      if (!term_exists($slug, FernFormPlugin::TAXONOMY_NAME)) {
        wp_insert_term($this->formName, FernFormPlugin::TAXONOMY_NAME, [
          'slug' => $slug,
        ]);
      }

      $postData = [
        'post_type'    => FernFormPlugin::POST_TYPE_NAME,
        'post_title'   => $title,
        'post_content' => $jsonContent,
        'post_status'  => 'publish',
        'meta_input'   => [
          Notifications::READ_STATUS_META_KEY => self::READ_STATUS,
        ],
        'tax_input'    => [
          FernFormPlugin::TAXONOMY_NAME => [$slug],
        ],
      ];

      $postId = wp_insert_post($postData);
      $isWpError = is_wp_error($postId);
    }

    if (!$isWpError) {
      $this->setId((int) $postId);
      $this->submission = $submission;

      $terms = wp_get_post_terms($postId, FernFormPlugin::TAXONOMY_NAME);

      if (!in_array($slug, $terms)) {
        wp_set_post_terms($postId, [$slug], FernFormPlugin::TAXONOMY_NAME);
      }

      /**
       * Fires after a submission is successfully stored.
       *
       * @param int $postId The submission ID.
       * @param string $formName The form name/identifier.
       * @param array<string, mixed> $submission The submission data.
       */
      do_action('fern:form:submission_stored', $postId, $slug, $submission);
      return $postId;
    }

    if ($isWpError) {
      /**
       * Fires when storage fails.
       *
       * @param \WP_Error $error The WordPress error object.
       * @param string $formName The form name/identifier.
       * @param array<string, mixed> $submission The submission data.
       */
      do_action('fern:form:submission_error', $isWpError, $slug, $submission);
      return null;
    }

    return null;
  }
}
