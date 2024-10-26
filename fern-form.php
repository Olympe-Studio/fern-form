<?php
/**
 * Plugin Name: Fern Form
 * Description: A modern form handling plugin with automatic submission cleanup
 * Version: 1.0.0
 * Requires PHP: 8.0
 * Requires at least: 5.9
 * Tested up to: 6.4
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

define('FERN_FORM_VERSION', '1.0.0');

spl_autoload_register(function (string $class) {
  $prefix = 'Fern\\Form\\';
  $baseDir = __DIR__ . '/src/';

  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    return;
  }

  $relativeClass = substr($class, $len);
  $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
  if (file_exists($file)) {
    require $file;
  }
});

use Fern\Form\FernFormPlugin;

$plugin = FernFormPlugin::getInstance();

add_action('plugins_loaded', [$plugin, 'boot']);
register_deactivation_hook(__FILE__, [$plugin, 'handleDeactivation']);

require_once __DIR__ . '/api.php';