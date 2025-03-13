<?php

/**
 * Component for rendering a submission item.
 *
 * @package Fern_Form
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}
?>
<div class="content-row <?php echo esc_attr($indent_class); ?>">
  <?php if (is_array($value)) : ?>
    <strong class="key">
      <?php
      /**
       * Filter the submission item key. Useful for translating.
       *
       * @param string $display_key The display key.
       * @param string $full_key The full key path.
       * @return string The filtered key.
       */
      echo apply_filters('fern:form:submission_item_key', $display_key, $full_key);
      ?>
    </strong>:
    <?php render_content_recursively($value, $depth + 1, $display_key); ?>
  <?php else : ?>
    <strong class="key">
      <?php
      /**
       * Filter the submission item key. Useful for translating.
       *
       * @param string $display_key The display key.
       * @param string $full_key The full key path.
       * @return string The filtered key.
       */
      echo apply_filters('fern:form:submission_item_key', $display_key, $full_key);
      ?>
    </strong>
    <?php if (is_string($value) && strlen($value) > 100) : ?>
      <div class="long-text">
        <?php
        /**
         * Filter the submission item value. Useful for translating.
         *
         * @param string $value The value to display.
         * @param string $display_key The display key.
         * @param string $full_key The full key path.
         * @return string The filtered value.
         */
        echo apply_filters('fern:form:submission_item_value', nl2br(esc_html((string) $value)), $display_key, $full_key);
        ?>
      </div>
    <?php else : ?>
      <span class="value">
        <?php
        if (is_bool($value)) {
          echo apply_filters('fern:form:submission_item_value', $value ? esc_html__('Yes', 'fern-form') : esc_html__('No', 'fern-form'), $display_key, $full_key);
        } elseif (is_null($value)) {
          echo esc_html__('Null', 'fern-form');
        } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
          require __DIR__ . '/url_value.php';
        } else {
          /**
           * Filter the submission item value. Useful for translating.
           *
           * @param mixed $value The value to display.
           * @param string $display_key The display key.
           * @param string $full_key The full key path.
           * @return mixed The filtered value.
           */
          $value = apply_filters('fern:form:submission_item_value', $value, $display_key, $full_key);
          echo esc_html((string) $value);
        }
        ?>
      </span>
    <?php endif; ?>
  <?php endif; ?>
</div>

<style>
  .content-row.indent-0 {
    margin-left: 0;
  }

  .content-row.indent-1 {
    margin-left: 0.5rem;
  }

  .content-row.indent-2 {
    margin-left: 1rem;
  }

  .content-row.indent-3 {
    margin-left: 1.5rem;
  }

  .content-row.indent-4 {
    margin-left: 2rem;
  }

  .content-row .key {
    margin-right: 0.25rem;
  }

  .content-row .long-text {
    white-space: pre-wrap;
  }
</style>