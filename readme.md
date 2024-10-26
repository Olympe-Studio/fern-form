=== Fern Form ===
Contributors: olympe-studio
Tags: forms, form-submissions, developers
Requires at least: 6.0
Tested up to: 6.6.2
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Description: A minimal form storage plugin for developers

# Fern Form

A lightweight WordPress plugin for handling form submissions for developers.
This plugin is made to work with the **Fern framework (WIP).**

## Features

- **Data Retention**: Configurable retention period for form submissions to prevent database bloat
- **Notification System**: Visual notifications for unread form submissions
- **Developer Friendly**: Extensive hooks and filters for customization
- **Zero table creation**: This plugin use the native WordPress post type & terms to store the form submissions.

**Be aware that this plugin use the post table to store the form submissions.**

Submissions are cleaned up by a daily wordpress cron job. If you disable the WP_CRON, the form submissions will not be deleted which could increase the post table size.

Default cleanup is set to 7 day after creation date.

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher

## Installation

1. Download the plugin from the WordPress repository or GitHub.
2. Upload the plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Basic Usage

Basic Form Submission

```php
// needs to be called after init hook
fern_form_store('contact_form', [
  'name' => 'John Doe',
  'email' => 'john@doe.com',
  'message' => 'Hello, world!'
]);

// or using the class
use Fern\Form\Includes\FormSubmission;

$formSubmission = new FormSubmission('contact_form', [
  'name' => 'John Doe',
  'email' => 'john@doe.com',
  'message' => 'Hello, world!'
]);

$formSubmission->store();
```

Then, the form data will be stored in the database and visible in the admin dashboard.

## Configuration

You can configure the plugin by hooking into the `fern:form:config` filter.

```php
add_filter('fern:form:config', function(array $config): array {
  return [
    // After 30 days, submissions will be deleted
    'retention_days' => 30,
    // Set the capabilities for the form post type
    'form_capabilities' => [
      'create' => 'edit_posts',
      'read' => 'read',
      'delete' => 'delete_posts'
    ]
  ];
});
```

## Hooks references

You can extend the plugin functionality by hooking into the following actions and filters.

### Actions

`fern:form:submission_stored`

Triggered after successful submission storage

```php
/**
 * @param int    $post_id     The ID of the newly created submission post
 * @param string $form_name   The sanitized form name/slug
 * @param array  $submission  The complete submission data array
 */
add_action('fern:form:submission_stored', function($post_id, $form_name, $submission) {
  // Example: Send email notification
  wp_mail(
    'admin@example.com',
    "New submission from {$form_name}",
    "Submission ID: {$post_id}"
  );
}, 10, 3);
```

`fern:form:submission_error`

Triggered on submission error

```php

/**
 * @param WP_Error $error       The WordPress error object
 * @param string   $form_name   The sanitized form name/slug
 * @param array    $submission  The submission data that failed to store
 */
add_action('fern:form:submission_error', function($error, $form_name, $submission) {
  // Example: Log the error
  error_log("Form submission failed for {$form_name}: " . $error->get_error_message());
}, 10, 3);
```

### Filters

`fern:form:config`

Modify plugin configuration

```php
/**
 * @param array $config Default configuration array
 * @return array Modified configuration
 */
add_filter('fern:form:config', function($config) {
  $config['retention_days'] = 60;
  $config['form_capabilities'] = [
    'create' => 'custom_cap',
    'read' => 'read_forms',
    'delete' => 'delete_forms'
  ];
  return $config;
});
```

`fern:form:submission_data`

Modify submission data before storage

```php
/**
 * @param array $submission The submission data array
 * @return array Modified submission data
 */
add_filter('fern:form:submission_data', function($submission) {
  // Example: Add timestamp
  $submission['submitted_at'] = current_time('mysql');
  return $submission;
});
```

`fern:form:submission_title`

Customize submission title format (in admin list view)

```php
/**
 * @param string $default_title Default title format
 * @param string $form_name     The form name/slug
 * @param array  $submission    The complete submission data
 * @return string Modified title
 */
add_filter('fern:form:submission_title', function($default_title, $form_name, $submission) {
  if (!empty($submission['email'])) {
    return $submission['email'];
  }

  return $default_title;
}, 10, 3);
```

## Uninstall

The plugin will automatically remove all data when uninstalled if the `FERN_CLEAR_ON_DEACTIVATE` constant is set to `true`.

```php
define('FERN_CLEAR_ON_DEACTIVATE', true);
```

Otherwise, data will be preserved.