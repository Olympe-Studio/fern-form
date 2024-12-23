<div class="content-row">
  <?= esc_html($indent); ?>

  <? if (is_array($value)):
    /**
     * Allow filtering of the submission item key. Usefull for translating.
     *
     * @param string $displayKey
     * @param string $key The key of the parent item
     *
     * @return string
     */
  ?><strong class="key"><?= apply_filters('fern:form:submission_item_key', $displayKey, $key); ?></strong>:
</div>
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
    <?= apply_filters('fern:form:submission_item_key', $displayKey, $key); ?></strong>

  <? if (is_string($value) && strlen($value) > 100): ?>
    <div class="long-text">
      <?= nl2br(esc_html((string) $value)); ?>
    </div>
  <? else: ?>
    <span class="value">
      <?
      if (is_bool($value)) {
        echo $value ? __('Yes', 'default') : __('No', 'default');
      } elseif (is_null($value)) {
        echo __('Null', 'default');
      } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
        require __DIR__ . '/url_value.php';
      } else {
        echo esc_html((string) $value);
      }
      ?>
    </span>
  <? endif; ?>
  </div>
<? endif; ?>