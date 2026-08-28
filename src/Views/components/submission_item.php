<div class="content-row <?= esc_attr($indentClass); ?>">
  <?php if (is_array($value)): ?>
    <?php
    /**
     * Allow filtering of the submission item key. Usefull for translating.
     *
     * @param string $displayKey
     * @param string $key The key of the parent item
     *
     * @return string
     */
    ?><strong class="key section-title"><?= apply_filters('fern:form:submission_item_key', ucwords(str_replace(['_', '-'], ' ', $displayKey)), $fullKey); ?></strong>
    <div class="nested-content">
      <?php render_content_recursively($value, $depth + 1, $displayKey); ?>
    </div>
  <?php else: ?>
    <strong class="key">
      <?php
      /**
       * Allow filtering of the submission item key. Usefull for translating.
       *
       * @param string $displayKey
       * @param string $key The key of the parent item
       *
       * @return string
       */
      ?>
      <?= apply_filters('fern:form:submission_item_key', ucwords(str_replace(['_', '-'], ' ', $displayKey)), $fullKey); ?>
    </strong>
    <?php if (is_string($value) && strlen($value) > 100): ?>
      <div class="long-text"><?php
        /**
         * Allow filtering of the submission item value. Usefull for translating.
         *
         * @param string $value
         * @param string $key The full key of the parent item
         *
         * @return string
         */
        $filteredValue = apply_filters('fern:form:submission_item_value', (string) $value, $displayKey, $fullKey);
        echo esc_html($filteredValue);
        ?></div>
    <?php else: ?>
      <span class="value">
        <?php
        $isBoolean = is_bool($value) || in_array(strtolower((string)$value), ['true', 'false', '1', '0'], true);
        
        if ($isBoolean) {
          $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);
          echo apply_filters('fern:form:submission_item_value', $boolValue ? __('Yes', 'default') : __('No', 'default'), $displayKey, $fullKey);
        } elseif (is_null($value)) {
          echo __('Null', 'default');
        } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
          require __DIR__ . '/url_value.php';
        } else {
          /**
           * Allow filtering of the submission item value. Usefull for translating.
           *
           * @param string $value
           * @param string $key The full key of the parent item
           *
           * @return string
           */
          $value = apply_filters('fern:form:submission_item_value', $value, $displayKey, $fullKey);
          
          // Capitalize value if it's not an email
          if (is_string($value) && !is_email($value)) {
             $value = ucfirst($value);
          }
          
          echo nl2br(esc_html((string) $value));
        }
        ?>
      </span>
    <?php endif; ?>
  <?php endif; ?>
</div>
