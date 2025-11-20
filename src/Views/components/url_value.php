<?php

/**
 * Component for rendering a URL value.
 *
 * @package Fern_Form
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}
?>
<a href="<?php echo esc_url($value); ?>" rel="noreferrer noopener nofollow noarchive" target="_blank" class="url-value">
  <?php echo esc_html($value); ?>
</a>