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
 */
?>


<?php
echo $this->insert('crud::create', $this->data());
?>

<script nonce="<?= $this->e($cspNonce) ?>">
    document.addEventListener('DOMContentLoaded', function() {
        function createEIKButtonWrapper(input) {
            const wrapper = document.createElement('div');
            wrapper.className = 'ui action input';

            const newInput = input.cloneNode(true);
            newInput.value = input.value;
            newInput.name = 'bulstat';
            newInput.id = 'bulstat';

            wrapper.appendChild(newInput);

            const btn = document.createElement('button');
            btn.className = 'ui blue button';
            btn.type = 'button';
            btn.textContent = 'Провери';

            let hiddenInput = document.querySelector('[name="check_count"]');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'check_count';
                hiddenInput.setAttribute('data-redraw', 'true');
                hiddenInput.value = 0;
                document.forms[0].appendChild(hiddenInput);
            }

            btn.addEventListener('click', function() {
                const eik = newInput.value.trim();
                if (!eik) {
                    alert('Моля, въведете ЕИК.');
                    return;
                }
                btn.classList.add('loading');

                fetch('CheckEIK', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            bulstat: eik
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.classList.remove('loading');

                        if (data.error) {
                            alert('Няма данни в търговския регистър');
                            return;
                        }
                        // console.log(data);

                        const companyName = document.querySelector('[name="company_name"]');
                        if (companyName && data.companyName) {
                            companyName.value = data.companyName;
                        }

                        const companyAddress = document.querySelector('[name="company_address"]');
                        if (companyAddress && data.fullAddress) {
                            companyAddress.value = data.fullAddress;
                        }

                        const companyEmail = document.querySelector('[name="company_email"]');
                        if (companyEmail && data.email) {
                            companyEmail.value = data.email;
                        }

                          const companyRegion = document.querySelector('[name="company_region"]');
                        if (companyRegion && data.location.region.id) {
                            companyRegion.value = data.location.region.id;
                        }

                        const companyMunicipality = document.querySelector('[name="company_municipality"]');
                        if (companyMunicipality && data.location.municipality.id) {
                            companyMunicipality.value = data.location.municipality.id;
                        }

                       const companyCity = document.querySelector('[name="company_city"]');
                        if (companyCity && data.location.city.id) {
                            companyCity.value = data.location.city.id;
                        }

                        hiddenInput.value = 1;
                        hiddenInput.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    })
                    .catch(() => {
                        btn.classList.remove('loading');
                        alert('Грешка при заявката');
                    });

            });

            wrapper.appendChild(btn);
            return {
                wrapper,
                label: newInput.labels?.[0]
            };
        }

        function injectEIKField() {
            const input = document.querySelector('[name="bulstat"]');
            const parentField = input?.closest('.field');

            if (!input || !parentField || parentField.querySelector('.ui.action.input')) return;

            const {
                wrapper
            } = createEIKButtonWrapper(input);
            parentField.innerHTML = '';
            const label = document.createElement('label');
            label.setAttribute('for', 'bulstat');
            label.textContent = 'ЕИК';
            parentField.appendChild(label);
            parentField.appendChild(wrapper);
        }


        injectEIKField();

        const observer = new MutationObserver(() => {
            injectEIKField();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
</script>


