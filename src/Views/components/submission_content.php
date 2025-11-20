<?

/**
 * Renders content recursively with proper indentation
 *
 * @param array $content The content to render
 * @param int $depth Current depth level
 * @param string $parentKey Parent key for nested items
 */
function render_content_recursively($content, $depth = 0, $parentKey = '') {
  foreach ($content as $key => $value) {
    $displayKey = esc_html($key); // Just use the current key, not the full path
    $fullKey = $parentKey ? "$parentKey.$key" : $key; // Keep full path for filtering
    $indentClass = "indent-" . $depth;
    require __DIR__ . '/submission_item.php';
  }
}
render_content_recursively($content);
