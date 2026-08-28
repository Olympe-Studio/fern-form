<?php

/**
 * Uninstall Fern Form
 *
 * @package Fern_Form
 */
// If uninstall not called from WordPress, exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

// Check if we should clear data on uninstall.
if (! defined('FERN_CLEAR_ON_DEACTIVATE') || ! FERN_CLEAR_ON_DEACTIVATE) {
  return;
}

/*
 * WordPress loads uninstall.php on its own, without the main plugin file, so
 * the autoloader registered there has never run at this point. Referencing
 * FernFormPlugin without it was a "class not found" fatal for exactly the
 * users who opted into FERN_CLEAR_ON_DEACTIVATE.
 */
require_once __DIR__ . '/fern-form.php';

use Fern\Form\FernFormPlugin;

$plugin = FernFormPlugin::getInstance();
$plugin->handleDeactivation();
