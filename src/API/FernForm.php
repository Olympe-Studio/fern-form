<?php

declare(strict_types=1);
if (!defined('ABSPATH')) {
  exit;
}

namespace Fern\Form\API;

use Fern\Form\Includes\FormSubmission;


final class FernForm {
  /**
   * Store a form submission.
   *
   * @param string $form_name The form name/identifier.
   * @param array<string, mixed> $submission The submission data.
   *
   * @return int|null The submission ID or null if storage failed.
   */
  public static function storeForm(string $form_name, array $submission): ?int {
    $form_submission = new FormSubmission($form_name, $submission);
    return $form_submission->store();
  }

  /**
   * Update a form submission.
   *
   * @param int $submission_id The submission ID.
   * @param array<string, mixed> $submission The new submission data.
   *
   * @return void
   */
  public static function updateForm(int $submission_id, array $submission): void {
    $form_submission = FormSubmission::getById($submission_id);
    if (is_null($form_submission)) {
      return;
    }

    $form_submission->update($submission);
  }

  /**
   * Get a form submission by ID.
   *
   * @param int $submission_id The submission ID.
   *
   * @return FormSubmission|null The submission object or null if not found.
   */
  public static function getSubmissionById(int $submission_id): ?FormSubmission {
    return FormSubmission::getById($submission_id);
  }

  /**
   * Delete a form submission.
   *
   * @param int $submission_id The submission ID.
   *
   * @return void
   */
  public static function deleteForm(int $submission_id): void {
    $form_submission = FormSubmission::getById($submission_id);
    if (is_null($form_submission)) {
      return;
    }

    $form_submission->delete();
  }
}
