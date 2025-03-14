<?php

/**
 * Template for displaying a form submission.
 *
 * @package Fern_Form
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}
?>
<div class="submission-wrapper">
  <?php require __DIR__ . '/components/submission_content.php'; ?>
</div>

<style>
  .submission-wrapper {
    font-family: monospace;
    font-size: 14px;
    line-height: 1.6;
    margin: 1rem 0;
  }

  .key {
    color: #2563eb;
  }

  .value {
    margin-left: 0.5rem;
  }

  .url-value {
    color: #059669;
    text-decoration: none;
  }

  .url-value:hover {
    text-decoration: underline;
  }
</style>