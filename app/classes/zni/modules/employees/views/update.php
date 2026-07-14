<?php

/**
 * @var \vakata\views\View $this
 * @var \vakata\http\Request $req
 * @var string $cspNonce
 * @var array $pkey
 * @var \vakata\http\Uri $url
 * @var callable (string): string $asset
 * @var \vakata\intl\Intl $intl
 * @var callable (string): mixed $config
 * @var \webadmin\modules\VisualModuleInterface $module
 */
?>
<?php
echo $this->insert('crud::update', $this->data());
?>

<script nonce="<?= $this->e($cspNonce) ?>">
document.addEventListener('DOMContentLoaded', function () {

  function parseToCents(value) {
    if (!value) return 0;

    let v = value.toString().trim()
      .replace(/\s/g, '')
      .replace(',', '.')
      .replace(/[^0-9.\-]/g, '');

    if (v === '' || v === '-' || v === '.') return 0;

    const negative = v.startsWith('-');
    if (negative) v = v.slice(1);

    const parts = v.split('.');
    const intPart = parts[0] || '0';
    let decPart = parts[1] || '';
    decPart = (decPart + '00').slice(0, 2);

    const cents = (parseInt(intPart, 10) * 100) + parseInt(decPart, 10);
    return negative ? -cents : cents;
  }

  function calculateTotals() {
    let salaryCents = 0;
    let insuranceCents = 0;

    document.querySelectorAll('input[name^="salary["]').forEach(input => {
      salaryCents += parseToCents(input.value);
    });

    document.querySelectorAll('input[name^="insurance["]').forEach(input => {
      insuranceCents += parseToCents(input.value);
    });

    const totalSalaryField = document.querySelector('input[name="totalSalary"]');
    if (totalSalaryField) totalSalaryField.value = (salaryCents / 100).toFixed(2);

    const totalInsuranceField = document.querySelector('input[name="totalInsurance"]');
    if (totalInsuranceField) totalInsuranceField.value = (insuranceCents / 100).toFixed(2);
  }

  document.addEventListener('input', function (e) {
    const n = e.target && e.target.name ? e.target.name : '';
    if (n.startsWith('salary[') || n.startsWith('insurance[')) {
      calculateTotals();
    }
  });

  calculateTotals();
});
</script>
