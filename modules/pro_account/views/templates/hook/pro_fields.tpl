<style>
    #pro-fields-container {
        display: none;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-top: 15px;
    }
</style>

<div class="form-group row ">
    <label class="col-md-3 form-control-label"></label>
    <div class="col-md-6">
    <span class="custom-checkbox">
      <input name="is_pro" id="is_pro_checkbox" type="checkbox">
      <span><i class="material-icons rtl-no-flip checkbox-checked">check</i></span>
      <label for="is_pro_checkbox">{l s='Je suis une entreprise' mod='pro_account'}</label>
    </span>
    </div>
</div>

<div id="pro-fields-container">

    <div class="form-group row ">
        <label class="col-md-3 form-control-label required">
            {l s='Nom de l\'entreprise' mod='pro_account'}
        </label>
        <div class="col-md-6">
            <input class="form-control" name="company_name" id="pro-company-name" type="text">
        </div>
    </div>

    <div class="form-group row ">
        <label class="col-md-3 form-control-label required">
            {l s='Numéro de SIRET' mod='pro_account'}
        </label>
        <div class="col-md-6">
            <input class="form-control" name="siret" id="pro-siret" type="text" maxlength="20">
        </div>
    </div>

    <div class="form-group row">
        <label class="col-md-3 form-control-label"></label>
        <div class="col-md-6">
            <button type="button" id="verify-siret-btn" class="btn btn-primary">
                {l s='Vérifier le Siret' mod='pro_account'}
            </button>
            <div id="siret-feedback" style="margin-top: 10px; font-weight: bold;"></div>
        </div>
    </div>

    <input type="hidden" name="siret_validated" id="siret-validated" value="0">

    <div class="form-text text-center" style="margin-top: 15px;">
        <a href="{$manual_validation_url}">
            {l s='Mon SIRET n\'est pas reconnu ?' mod='pro_account'}
        </a>
    </div>
</div>


<script>
    {literal}
    document.addEventListener('DOMContentLoaded', function() {

        const isProCheckbox = document.getElementById('is_pro_checkbox');
        const proFieldsContainer = document.getElementById('pro-fields-container');

        function toggleProFields() {
            proFieldsContainer.style.display = isProCheckbox.checked ? 'block' : 'none';
        }

        isProCheckbox.addEventListener('change', toggleProFields);
        toggleProFields(); // Appel initial

        const verifyBtn = document.getElementById('verify-siret-btn');
        const siretInput = document.getElementById('pro-siret');
        const companyInput = document.getElementById('pro-company-name');
        const feedbackDiv = document.getElementById('siret-feedback');
        const validatedInput = document.getElementById('siret-validated');

        // L'URL est directement injectée par PHP/Smarty dans la balise <script> parente
        const ajaxUrl = '{/literal}{$pro_account_ajax_url}{literal}';

        // Si l'utilisateur met des espaces, les supprime en tant réels
        siretInput.addEventListener('input', function() {
            this.value = this.value.replace(/\s/g, '');
        });

        // Si l'utilisateur modifie le SIRET, on reset la validation
        siretInput.addEventListener('input', () => {
            validatedInput.value = '0';
            feedbackDiv.innerHTML = '';
        });

        verifyBtn.addEventListener('click', function() {
            const siret = siretInput.value.replace(/\s/g, '');
            validatedInput.value = '0';

            if (!/^[0-9]{14}$/.test(siret)) {
                feedbackDiv.style.color = 'red';
                feedbackDiv.innerHTML = 'Format invalide (14 chiffres requis).';
                return;
            }

            feedbackDiv.style.color = 'orange';
            feedbackDiv.innerHTML = 'Vérification en cours...';

            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=validateSiret&siret=' + siret
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        feedbackDiv.style.color = 'green';
                        feedbackDiv.innerHTML = `✓ Valide : ${data.company_name}`;
                        companyInput.value = data.company_name;
                        validatedInput.value = '1'; // On valide !
                    } else {
                        feedbackDiv.style.color = 'red';
                        feedbackDiv.innerHTML = `✗ ${data.message}`;
                    }
                })
                .catch(error => {
                    console.error('Erreur Fetch:', error);
                    feedbackDiv.style.color = 'red';
                    feedbackDiv.innerHTML = '✗ Erreur de communication.';
                });
        });
    });
    {/literal}
</script>