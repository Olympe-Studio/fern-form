<?
function render_content_recursively($content, $depth = 0, $parentKey = '') {
  $indent = str_repeat('  ', $depth);

  foreach ($content as $key => $value) {
    $displayKey = esc_html($parentKey ? "$parentKey.$key" : $key);
    require __DIR__ . '/submission_item.php';
  }
}

render_content_recursively($content);
