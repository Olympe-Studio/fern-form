<?php

/**
 * Plugin Name: Fern Form
 * Plugin URI: https://github.com/Olympe-Studio/fern-form
 * Description: A minimal form storage plugin for developers
 * Author: Tanguy Magnaudet <tanguy@olympe-studio.xyz>
 * Author URI: https://www.olympe-studio.xyz
 * Version: 2.0.1
 * @copyright Copyright (c) 2024 fern-form
 * @license MIT
 * Text Domain: fern-form
 * Requires PHP: 8.0
 * Requires at least: 5.9
 * Tested up to: 6.6.2
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

define('FERN_FORM_VERSION', '2.0.1');
define('FERN_FORM_DIR', __DIR__);

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
