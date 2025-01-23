<?php

declare(strict_types=1);
if (!defined('ABSPATH')) {
  exit;
}

namespace Fern\Form\API;

use Fern\Form\Includes\FormSubmission;


final class FernForm {
  /**
   * @param string $formName
   * @param array<string, mixed> $submission
   *
   * @return int|null
   */
  public static function storeForm(string $formName, array $submission): ?int {
    $formSubmission = new FormSubmission($formName, $submission);
    return $formSubmission->store();
  }

  /**
   * @param int $submissionId
   * @param array<string, mixed> $submission
   *
   * @return void
   */
  public static function updateForm(int $submissionId, array $submission): void {
    $formSubmission = FormSubmission::getById($submissionId);
    if (is_null($formSubmission)) {
      return;
    }

    $formSubmission->update($submission);
  }

  /**
   * @param int $submissionId
   *
   * @return FormSubmission|null
   */
  public static function getSubmissionById(int $submissionId): ?FormSubmission {
    return FormSubmission::getById($submissionId);
  }

  /**
   * @param int $submissionId
   *
   * @return void
   */
  public static function deleteForm(int $submissionId): void {
    $formSubmission = FormSubmission::getById($submissionId);
    if (is_null($formSubmission)) {
      return;
    }

    $formSubmission->delete();
  }
}
