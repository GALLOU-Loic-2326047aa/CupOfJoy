<div class="rental-container">

    {* Offre 12 mois *}
    {if isset($price_per_month_12) && $price_per_month_12 > 0}
        <div class="rental-offer-box border p-3 mb-3">
            <h4>{l s='Offre 12 mois' mod='rentalroute'}</h4>
            <p class="h5">
                <strong>{$price_per_month_12_formatted}</strong> / {l s='mois' mod='rentalroute'}
            </p>
            {if $customer.is_logged}
                <button type="button" class="btn btn-primary btn-block mt-2 js-rental-submit" data-duration="12">
                    {l s='Louer pour 12 mois' mod='rentalroute'}
                </button>
            {else}
                <a href="{$urls.pages.authentication}?back={$urls.current_url}" class="btn btn-primary btn-block mt-2">
                    {l s='Se connecter pour louer' mod='rentalroute'}
                </a>
            {/if}
        </div>
    {/if}

    {* Offre 36 mois *}
    {if isset($price_per_month_36) && $price_per_month_36 > 0}
        <div class="rental-offer-box border p-3 mb-3">
            <h4>{l s='Offre 36 mois' mod='rentalroute'}</h4>
            <p class="h5">
                <strong>{$price_per_month_36_formatted}</strong> / {l s='mois' mod='rentalroute'}
            </p>
            {if $customer.is_logged}
                <button type="button" class="btn btn-primary btn-block mt-2 js-rental-submit" data-duration="36">
                    {l s='Louer pour 36 mois' mod='rentalroute'}
                </button>
            {else}
                <a href="{$urls.pages.authentication}?back={$urls.current_url}" class="btn btn-primary btn-block mt-2">
                    {l s='Se connecter pour louer' mod='rentalroute'}
                </a>
            {/if}
        </div>
    {/if}

    {* Affichage des frais annexes *}
    <div class="rental-fees mt-3 small">
        {if isset($deposit_amount) && $deposit_amount > 0}
            <p>{l s='Un dépôt de garantie de' mod='rentalroute'} {$deposit_amount_formatted} {l s='sera requis.' mod='rentalroute'}</p>
        {/if}
        {if isset($installation_fee) && $installation_fee > 0}
            <p>{l s='Des frais d\'installation de' mod='rentalroute'} {$installation_fee_formatted} {l s='s\'appliquent.' mod='rentalroute'}</p>
        {/if}
    </div>

    {* Le script JS qui gère les deux boutons *}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var rentalButtons = document.querySelectorAll('.js-rental-submit');

            rentalButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var duration = this.getAttribute('data-duration'); // Récupère la durée (12 ou 36)

                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{$booking_url nofilter}';
                    form.style.display = 'none';

                    // Ajout de l'ID produit
                    var productIdInput = document.createElement('input');
                    productIdInput.type = 'hidden';
                    productIdInput.name = 'id_product';
                    productIdInput.value = '{$rental_product_id}';
                    form.appendChild(productIdInput);

                    // Ajout de la durée
                    var durationInput = document.createElement('input');
                    durationInput.type = 'hidden';
                    durationInput.name = 'rental_duration';
                    durationInput.value = duration;
                    form.appendChild(durationInput);

                    // Ajout de la quantité (fixée à 1)
                    var quantityInput = document.createElement('input');
                    quantityInput.type = 'hidden';
                    quantityInput.name = 'quantity';
                    quantityInput.value = '1';
                    form.appendChild(quantityInput);

                    // Ajout du flag de soumission
                    var submitFlag = document.createElement('input');
                    submitFlag.type = 'hidden';
                    submitFlag.name = 'submitRental';
                    submitFlag.value = '1';
                    form.appendChild(submitFlag);

                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });
    </script>
</div>