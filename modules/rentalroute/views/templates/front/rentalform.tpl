<div class="rental-form-container">
    <h4>{l s='Louer ce produit' mod='rentalroute'}</h4>

    <div class="rental-fees mb-3">
        {if isset($deposit_amount) && $deposit_amount > 0}
            <div class="rental-info">
                <strong>{l s='Dépôt de garantie:' mod='rentalroute'}</strong>
                <span class="rental-fee-amount">{$deposit_amount_formatted}</span>
            </div>
        {/if}
        {if isset($installation_fee) && $installation_fee > 0}
            <div class="rental-info">
                <strong>{l s='Frais d\'installation:' mod='rentalroute'}</strong>
                <span class="rental-fee-amount">{$installation_fee_formatted}</span>
            </div>
        {/if}
    </div>

    {if $customer.is_logged}

        <div id="rental-fake-form">
            <input type="hidden" name="id_product" value="{$rental_product_id}">
            <div class="form-group">
                <label>{l s='Quantité' mod='rentalroute'}</label>
                <input type="number" name="quantity" class="form-control" value="1" min="1">
            </div>
            <div class="form-group">
                <label>{l s='Date de début' mod='rentalroute'}</label>
                <input type="date" name="date_start" class="form-control" required>
            </div>
            <div class="form-group">
                <label>{l s='Date de fin' mod='rentalroute'}</label>
                <input type="date" name="date_end" class="form-control" required>
            </div>

            <button type="button" id="rental-submit-btn" class="btn btn-primary">{l s='Vérifier la disponibilité et réserver' mod='rentalroute'}</button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var rentalBtn = document.getElementById('rental-submit-btn');
                if (rentalBtn) {
                    rentalBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{$booking_url nofilter}';
                        form.style.display = 'none';

                        var container = document.getElementById('rental-fake-form');
                        var inputs = container.querySelectorAll('input, select');
                        for (var i = 0; i < inputs.length; i++) {
                            var clone = document.createElement('input');
                            clone.type = 'hidden';
                            clone.name = inputs[i].name;
                            clone.value = inputs[i].value;
                            form.appendChild(clone);
                        }

                        var submitFlag = document.createElement('input');
                        submitFlag.type = 'hidden';
                        submitFlag.name = 'submitRental';
                        submitFlag.value = '1';
                        form.appendChild(submitFlag);

                        document.body.appendChild(form);
                        form.submit();
                    });
                }
            });
        </script>

    {else}

        <div class="alert alert-info">
            <p>{l s='Vous devez être connecté pour louer ce produit.' mod='rentalroute'}</p>
            <a href="{$urls.pages.authentication}?back={$urls.current_url}" class="btn btn-primary">
                {l s='Se connecter ou créer un compte' mod='rentalroute'}
            </a>
        </div>

    {/if}
</div>
