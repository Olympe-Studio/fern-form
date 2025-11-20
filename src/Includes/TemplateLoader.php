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
   * @param string $template Template name
   * @param array<string, mixed> $data Data to pass to template
   * @return void
   */
  public static function render(string $template, array $data = []): void {
    ob_start();
    extract($data);
    require trailingslashit(FernFormPlugin::PLUGIN_DIR) . "Views/$template.php";
    $content = ob_get_clean();
    echo $content;
  }
}
