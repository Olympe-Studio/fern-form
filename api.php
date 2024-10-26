<?php
if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\API\FernForm;


if (!function_exists('fern_form_store')) {
  /**
   * Store a form submission.
   *
   * @param string $formName
   * @param array<string, mixed> $submission
   *
   * @return void
   */
  function fern_form_store(string $formName, array $submission): void {
    FernForm::storeForm($formName, $submission);
  }
}
