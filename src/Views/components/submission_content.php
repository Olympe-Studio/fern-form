<?php

/**
 * Component for rendering submission content.
 *
 * @package Fern_Form
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
  exit;
}

/**
 * Renders content recursively with proper indentation
 *
 * @param array $content The content to render
 * @param int $depth Current depth level
 * @param string $parent_key Parent key for nested items
 */
function render_content_recursively($content, $depth = 0, $parent_key = '') {
  foreach ($content as $key => $value) {
    $display_key = esc_html($key); // Just use the current key, not the full path
    $full_key = $parent_key ? "$parent_key.$key" : $key; // Keep full path for filtering
    $indent_class = "indent-" . $depth;
    require __DIR__ . '/submission_item.php';
  }
}

// Render the content.
render_content_recursively($content);
