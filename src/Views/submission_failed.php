<?php

/**
 * Template for displaying a failed form submission.
 *
 * @package Fern_Form
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}
?>
<div class="submission-error">
  <p><?php echo esc_html__('No content found', 'fern-form'); ?></p>
  <pre>
		<?php
    // Only show post details to users who can edit posts.
    if (current_user_can('edit_posts')) {
      echo esc_html(print_r($post, true));
    }
    ?>
	</pre>
</div>