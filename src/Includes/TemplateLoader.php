<?php

declare(strict_types=1);

namespace Fern\Form\Includes;


if (!defined('ABSPATH')) {
  exit;
}

use Fern\Form\FernFormPlugin;

/**
 * Template loader for Fern Form.
 */
class TemplateLoader {
  /**
   * Load a template file.
   *
   * @param string $template Template name.
   * @param array<string, mixed> $data Data to pass to template.
   * @return void
   */
  public static function render(string $template, array $data = []): void {
    // Sanitize template name to prevent directory traversal.
    $template = sanitize_file_name($template);

    // Start output buffering.
    ob_start();

    // Extract variables for use in template.
    extract($data, EXTR_SKIP);

    // Include the template file.
    $template_path = trailingslashit(FernFormPlugin::PLUGIN_DIR) . "Views/$template.php";
    if (file_exists($template_path)) {
      include $template_path;
    }

    // Get and output the buffer contents.
    $content = ob_get_clean();
    echo $content;
  }
}
