=== Fern Form ===
Contributors: olympe-studio
Tags: forms, form-submissions, developers
Requires at least: 6.0
Tested up to: 6.6.2
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A minimal form storage plugin for developers that stores form submissions as WordPress posts.

== Description ==

Fern Form is a lightweight WordPress plugin for handling form submissions for developers.
This plugin is made to work with the **Fern framework (WIP).**

= Features =

* **Data Retention**: Configurable retention period for form submissions to prevent database bloat
* **Notification System**: Visual notifications for unread form submissions
* **Developer Friendly**: Extensive hooks and filters for customization
* **Zero table creation**: Uses the native WordPress post type & terms to store form submissions

**Note:** This plugin uses the WordPress post table to store form submissions.

Submissions are cleaned up by a daily WordPress cron job. If you disable WP_CRON, the form submissions will not be deleted, which could increase the post table size.

Default cleanup is set to 7 days after creation date.

== Requirements ==

* WordPress 6.0 or higher
* PHP 8.0 or higher

== Installation ==

1. Download the plugin from the GitHub releases.
2. Upload the plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

= Using Composer =

First, edit the `composer.json` file to add the plugin install path:

```json
{
  ...
  "extra": {
    "installer-paths": {
      "public/content/plugins/fern-form/": [
        "fern/form"
      ]
    }
  }
}
```

Then, run the following command:

```bash
composer require fern/form
```

The plugin will be installed in the `public/content/plugins/fern-form` directory.

== Usage ==

= Basic Form Submission =

```php
// Needs to be called after init hook
fern_form_store('contact_form', [
  'name' => 'John Doe',
  'email' => 'john@doe.com',
  'message' => 'Hello, world!'
]);

// Or using the class
use Fern\Form\Includes\FormSubmission;

$formSubmission = new FormSubmission('contact_form', [
  'name' => 'John Doe',
  'email' => 'john@doe.com',
  'message' => 'Hello, world!'
]);

$entryId = $formSubmission->store();
```

The form data will be stored in the database and visible in the admin dashboard.

= Updating a Submission =

```php
$submissionId = 123;

// Using the helper function (needs to be called after init hook)
fern_form_update($submissionId, [
  'name' => 'John Doe',
  'email' => 'john@doe.com',
  'message' => 'Hello, world! (again)'
]);

// Or using the class
use Fern\Form\Includes\FormSubmission;

$submission = FormSubmission::getById($submissionId);
if ($submission) {
  $submission->update([
    'name' => 'John Doe',
    'email' => 'john@doe.com',
    'message' => 'Hello, world! (again)'
  ]);
}
```

= Deleting a Submission =

```php
$submissionId = 123;

// Using the helper function (needs to be called after init hook)
fern_form_delete($submissionId);

// Or using the class
use Fern\Form\Includes\FormSubmission;

$submission = FormSubmission::getById($submissionId);
if ($submission) {
  $submission->delete();
}
```

= Getting a Submission =

```php
$submissionId = 123;

// Using the helper function
$submission = fern_form_get_submission_by_id($submissionId);

if (is_null($submission)) {
  return;
}

// Do something with the submission

// Or using the class
use Fern\Form\Includes\FormSubmission;

$submission = FormSubmission::getById($submissionId);
if ($submission) {
  $data = $submission->getData();
  $formName = $submission->getFormName();
  
  // Do something with the data
}
```

= FormSubmission Class Methods =

The `FormSubmission` class provides the following methods:

```php
use Fern\Form\Includes\FormSubmission;

// Create a new form submission
$formSubmission = new FormSubmission('contact_form', [
  'name' => 'John Doe',
  'email' => 'john@doe.com',
  'message' => 'Hello, world!'
]);

// Store the submission
$id = $formSubmission->store();

// Get a submission by ID
$submission = FormSubmission::getById(123);

// Get submission data
$data = $submission->getData();

// Get form name
$formName = $submission->getFormName();

// Get submission ID
$id = $submission->getId();

// Set submission data
$submission->setData([
  'name' => 'Jane Doe',
  'email' => 'jane@doe.com'
]);

// Update submission
$submission->update([
  'name' => 'Jane Doe',
  'email' => 'jane@doe.com'
]);

// Delete submission
$submission->delete();
```

== Configuration ==

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

== Hooks Reference ==

You can extend the plugin functionality by hooking into the following actions and filters.

= Actions =

**fern:form:submission_stored**

Triggered after successful submission storage.

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

**fern:form:submission_error**

Triggered on submission error.

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

**fern:form:before_delete**

Triggered before deleting a submission.

```php
/**
 * @param int    $id         The submission post ID
 * @param string $form_name  The form name/slug
 */
add_action('fern:form:before_delete', function($id, $form_name) {
  // Custom logic before deletion
}, 10, 2);
```

**fern:form:after_delete**

Triggered after successful deletion.

```php
/**
 * @param int    $id         The deleted submission ID
 * @param string $form_name  The form name/slug
 */
add_action('fern:form:after_delete', function($id, $form_name) {
  // Post-deletion processing
}, 10, 2);
```

**fern:form:update_submission_error**

Triggered on update submission error.

```php
/**
 * @param int    $id         The submission ID
 * @param string $form_name  The form name/slug
 * @param array  $submission The submission data
 */
add_action('fern:form:update_submission_error', function($id, $form_name, $submission) {
  error_log("Update failed for submission {$id}");
}, 10, 3);
```

**fern:form:submission_updated**

Triggered after successful update.

```php
/**
 * @param int    $id         The submission ID
 * @param string $form_name  The form name/slug
 * @param array  $submission The updated data
 */
add_action('fern:form:submission_updated', function($id, $form_name, $submission) {
  // Post-update processing
}, 10, 3);
```

= Filters =

**fern:form:config**

Modify plugin configuration.

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

**fern:form:submission_should_abort**

Allow aborting the submission.

```php
/**
 * @param bool   $shouldAbort Whether to abort the submission
 * @param string $formName    The form name/slug
 * @param array  $submission  The submission data
 * @return bool  Whether to abort the submission
 */
add_filter('fern:form:submission_should_abort', function($shouldAbort, $formName, $submission) {
  // Example: Validate reCAPTCHA
  return ReCaptchaV3::validate($submission['recaptcha_token']);
}, 10, 3);
```

**fern:form:submission_data**

Modify submission data before storage.

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

**fern:form:submission_title**

Customize submission title format (in admin list view).

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

**fern:form:update_submission_should_abort**

Allow aborting the update submission.

```php
/**
 * @param bool   $should_abort Whether to abort
 * @param string $form_name    The form name/slug
 * @param array  $submission   The submission data
 * @return bool  Whether to abort the update
 */
add_filter('fern:form:update_submission_should_abort', function($should_abort, $form_name, $submission) {
  return $should_abort || !current_user_can('edit_posts');
}, 10, 3);
```

**fern:form:update_submission_data**

Modify submission data before update.

```php
/**
 * @param array $submission The submission data
 * @return array Modified data
 */
add_filter('fern:form:update_submission_data', function($submission) {
  $submission['updated_at'] = current_time('mysql');
  return $submission;
});
```

**fern:form:update_submission_title**

Customize submission title format when updating.

```php
/**
 * @param string $current_title Current post title
 * @param string $form_name     The form name/slug
 * @param array  $submission    The submission data
 * @return string New title
 */
add_filter('fern:form:update_submission_title', function($current_title, $form_name, $submission) {
  return $current_title . ' (Updated)';
}, 10, 3);
```

**fern:form:is_text_area**

Determine if a field should be treated as a text area for sanitization.

```php
/**
 * @param bool   $isTextArea    Whether the field is a text area
 * @param string $formName      The form name/slug
 * @param string $key           The original field key
 * @param string $sanitizedKey  The sanitized field key
 * @param string $value         The field value
 * @return bool  Whether the field should be treated as a text area
 */
add_filter('fern:form:is_text_area', function($isTextArea, $formName, $key, $sanitizedKey, $value) {
  // Treat fields with 'message' or 'description' in the name as text areas
  if (strpos($key, 'message') !== false || strpos($key, 'description') !== false) {
    return true;
  }
  return $isTextArea;
}, 10, 5);
```

**fern:form:min_long_text_words**

Customize the minimum number of words for a field to be treated as long text.

```php
/**
 * @param int $minLongTextWords Default minimum word count (7)
 * @return int Modified minimum word count
 */
add_filter('fern:form:min_long_text_words', function($minLongTextWords) {
  // Change the minimum word count to 10
  return 10;
});
```

**fern:form:delete_submission_should_abort**

Allow aborting the delete submission.

```php
/**
 * @param bool   $should_abort Whether to abort
 * @param string $form_name    The form name/slug
 * @param array  $submission   The submission data
 * @return bool  Whether to abort the deletion
 */
add_filter('fern:form:delete_submission_should_abort', function($should_abort, $form_name, $submission) {
  return $should_abort || !current_user_can('delete_posts');
}, 10, 3);
```

== Uninstall ==

The plugin will automatically remove all data when uninstalled if the `FERN_CLEAR_ON_DEACTIVATE` constant is set to `true`.

```php
define('FERN_CLEAR_ON_DEACTIVATE', true);
```

Otherwise, data will be preserved.

== Changelog ==

= 1.1.0 =
* Added FormSubmission class methods documentation
* Improved code documentation
* Added new filters for text area detection and sanitization

= 1.0.0 =
* Initial release