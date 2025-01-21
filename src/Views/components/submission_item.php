<div class="content-row <?= esc_attr($indentClass); ?>">
  <? if (is_array($value)): ?>
    <?
    /**
     * Allow filtering of the submission item key. Usefull for translating.
     *
     * @param string $displayKey
     * @param string $key The key of the parent item
     *
     * @return string
     */
    ?><strong class="key"><?= apply_filters('fern:form:submission_item_key', $displayKey, $fullKey); ?></strong>:
    <? render_content_recursively($value, $depth + 1, $displayKey); ?>
  <? else: ?>
    <strong class="key">
      <?
      /**
       * Allow filtering of the submission item key. Usefull for translating.
       *
       * @param string $displayKey
       * @param string $key The key of the parent item
       *
       * @return string
       */
      ?>
      <?= apply_filters('fern:form:submission_item_key', $displayKey, $fullKey); ?>
    </strong>
    <? if (is_string($value) && strlen($value) > 100): ?>
      <div class="long-text">
        <?
        /**
         * Allow filtering of the submission item value. Usefull for translating.
         *
         * @param string $value
         * @param string $key The full key of the parent item
         *
         * @return string
         */
        ?>
        <?= apply_filters('fern:form:submission_item_value', nl2br(esc_html((string) $value)), $displayKey, $fullKey); ?>
      </div>
    <? else: ?>
      <span class="value">
        <?
        if (is_bool($value)) {
          echo apply_filters('fern:form:submission_item_value', $value ? __('Yes', 'default') : __('No', 'default'), $displayKey, $fullKey);
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
          echo esc_html((string) $value);
        }
        ?>
      </span>
    <? endif; ?>
  <? endif; ?>
</div>

<style>
  .content-row {
    padding: 0.25rem 0;
  }

  .content-row.indent-0 {
    margin-left: 0;
  }

  .content-row.indent-1 {
    margin-left: 0.5rem;
  }

  .content-row.indent-2 {
    margin-left: 1rem;
  }

  .content-row.indent-3 {
    margin-left: 1.5rem;
  }

  .content-row.indent-4 {
    margin-left: 2rem;
  }

  .content-row .key {
    margin-right: 0.5rem;
  }

  .content-row .long-text {
    margin-top: 0.5rem;
    white-space: pre-wrap;
  }
</style>