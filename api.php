<?php
if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\API\FernForm;
use Fern\Form\Includes\FormSubmission;

if (!function_exists('fern_form_store')) {
  /**
   * Store a form submission.
   *
   * @param string $form_name The form name/identifier.
   * @param array<string, mixed> $submission The submission data.
   *
   * @return int|null The submission ID or null if storage failed.
   */
  function fern_form_store(string $form_name, array $submission): ?int {
    return FernForm::storeForm($form_name, $submission);
  }
}

if (!function_exists('fern_form_update')) {
  /**
   * Update a form submission.
   *
   * @param int $submission_id The submission ID.
   * @param array<string, mixed> $submission The new submission data.
   *
   * @return void
   */
  function fern_form_update(int $submission_id, array $submission): void {
    FernForm::updateForm($submission_id, $submission);
  }
}

if (! function_exists('fern_form_delete')) {
  /**
   * Delete a form submission.
   *
   * @param int $submission_id The submission ID.
   *
   * @return void
   */
  function fern_form_delete(int $submission_id): void {
    FernForm::deleteForm($submission_id);
  }
}

if (!function_exists('fern_form_get_submission_by_id')) {
  /**
   * Get a form submission by id.
   *
   * @param int $submission_id The submission ID.
   *
   * @return FormSubmission|null The submission object or null if not found.
   */
  function fern_form_get_submission_by_id(int $submission_id): ?FormSubmission {
    return FernForm::getSubmissionById($submission_id);
  }
}
