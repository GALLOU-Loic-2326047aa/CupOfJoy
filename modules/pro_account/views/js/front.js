document.addEventListener('DOMContentLoaded', function() {

    // --- Gérer l'affichage des champs ---
    const isProCheckbox = document.querySelector('input[name="is_pro"]');
    if (!isProCheckbox) return;

    const proFields = document.querySelectorAll('.pro-field');
    const siretInput = document.querySelector('input[name="siret"]');
    const companyNameInput = document.querySelector('input[name="company_name"]');
    const submitButton = document.querySelector('#customer-form button[type="submit"]');

    function toggleProFields() {
        let isPro = isProCheckbox.checked;
        proFields.forEach(function(fieldInput) {
            const formGroup = fieldInput.closest('.form-group');
            if (formGroup) {
                formGroup.style.display = isPro ? 'block' : 'none';
            }
        });
        if (!isPro && submitButton) {
            submitButton.disabled = false;
        }
    }
    toggleProFields();
    isProCheckbox.addEventListener('change', toggleProFields);

    // --- Validation AJAX du SIRET ---
    if (siretInput) {
        const feedbackElement = document.createElement('span');
        feedbackElement.className = 'siret-validation-feedback ml-2';
        siretInput.parentNode.appendChild(feedbackElement);

        siretInput.addEventListener('blur', function() {
            const siret = this.value.replace(/\s/g, '');

            if (isProCheckbox.checked && siret.length === 14 && /^[0-9]{14}$/.test(siret)) {
                console.log("SIRET valide, envoi de la requête AJAX à :", proAccountAjaxUrl); // @TODO sup
                feedbackElement.innerHTML = '&#8987;';
                if (submitButton) submitButton.disabled = true;
                console.log('ici')

                fetch(proAccountAjaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'action=validateSiret&siret=' + siret
                })
                    .then(response => {
                        console.log("Réponse brute du serveur :", response); // @TODO sup
                        // On vérifie que la réponse est bien du JSON
                        if (!response.headers.get('content-type')?.includes('application/json')) {
                            console.error("La réponse n'est pas du JSON !");
                            return null;
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data) return;
                        console.log("Données JSON reçues :", data); // @TODO sup
                        if (data.valid) {
                            feedbackElement.innerHTML = '&#10004;';
                            feedbackElement.style.color = 'green';
                            if (companyNameInput && data.company_name) companyNameInput.value = data.company_name;
                            if (submitButton) submitButton.disabled = false;
                        } else {
                            feedbackElement.innerHTML = '&#10006; SIRET invalide';
                            feedbackElement.style.color = 'red';
                        }
                    })
                    .catch(error => {
                        console.error("Erreur dans le fetch :", error); // @TODO sup
                        feedbackElement.innerHTML = '&#10006; Erreur de vérification';
                        feedbackElement.style.color = 'red';
                    });
            } else if (isProCheckbox.checked && siret.length > 0) {
                feedbackElement.innerHTML = '&#10006; Format invalide (14 chiffres requis)';
                feedbackElement.style.color = 'red';
                if (submitButton) submitButton.disabled = true;
            } else {
                feedbackElement.innerHTML = '';
                if (submitButton) submitButton.disabled = false;
            }
        });
    }

    // --- Affichage du logo pro ---
    if (typeof customerIsPro !== 'undefined' && customerIsPro === true) {
        const customerNameLink = document.querySelector('#_desktop_user_info .account > .hidden-sm-down');

        if (customerNameLink) {
            const proBadge = document.createElement('span');
            proBadge.innerText = 'PRO';
            proBadge.style.backgroundColor = '#007bff';
            proBadge.style.color = 'white';
            proBadge.style.padding = '2px 6px';
            proBadge.style.fontSize = '10px';
            proBadge.style.fontWeight = 'bold';
            proBadge.style.borderRadius = '4px';
            proBadge.style.marginLeft = '8px';
            proBadge.style.verticalAlign = 'middle';

            customerNameLink.appendChild(proBadge);
        }
    }

});
