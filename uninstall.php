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

use Fern\Form\FernFormPlugin;

$plugin = FernFormPlugin::getInstance();
$plugin->handleDeactivation();
