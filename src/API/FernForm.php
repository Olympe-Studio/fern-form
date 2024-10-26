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
   */
  public static function storeForm(string $formName, array $submission): void {
    $formSubmission = new FormSubmission($formName, $submission);
    $formSubmission->store();
  }
}
