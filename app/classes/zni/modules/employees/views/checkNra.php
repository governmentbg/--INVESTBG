<?php

/**
 * @var \vakata\views\View $this
 * @var \webadmin\components\html\Field $field
 * @var string $cspNonce
 */

?>
<button type="button" class="ui teal mini button" id="custom-redraw-btn">
    <i class="check icon"></i> Направи справка
</button>

<script nonce="<?= $this->e($cspNonce) ?>">
(function () {

  if (window.__nraBound) return;
  window.__nraBound = true;

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('#custom-redraw-btn');
    if (!btn) return;

    const hidden = document.querySelector('[name="checkNraHidden"]');
    if (!hidden) return;

    hidden.value = String((parseInt(hidden.value || '0', 10) + 1));
    hidden.dispatchEvent(new Event('change', { bubbles: true }));
    hidden.dispatchEvent(new Event('input',  { bubbles: true }));
  }, true);
})();
</script>