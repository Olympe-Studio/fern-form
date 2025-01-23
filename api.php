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
   * @param string $formName
   * @param array<string, mixed> $submission
   *
   * @return int|null
   */
  function fern_form_store(string $formName, array $submission): ?int {
    return FernForm::storeForm($formName, $submission);
  }
}

if (!function_exists('fern_form_update')) {
  /**
   * Update a form submission.
   *
   * @param string $formName
   * @param array<string, mixed> $submission
   *
   * @return void
   */
  function fern_form_update(int $submissionId, array $submission): void {
    FernForm::updateForm($submissionId, $submission);
  }
}

if (!function_exists('fern_form_delete')) {
  /**
   * Delete a form submission.
   *
   * @param int $submissionId
   *
   * @return void
   */
  function fern_form_delete(int $submissionId): void {
    FernForm::deleteForm($submissionId);
  }
}

if (!function_exists('fern_form_get_submission_by_id')) {
  /**
   * Get a form submission by id.
   *
   * @param int $submissionId
   *
   * @return FormSubmission|null
   */
  function fern_form_get_submission_by_id(int $submissionId): ?FormSubmission {
    return FernForm::getSubmissionById($submissionId);
  }
}
