<div class="form-group">
    <label class="control-label col-lg-3">{l s='Produit en location (Test)' mod='rentalroute'}</label>
    <div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
            <input type="radio" name="is_rental" id="is_rental_on" value="1" {if $is_rental == 1}checked="checked"{/if}>
            <label for="is_rental_on">{l s='Oui' mod='rentalroute'}</label>
            <input type="radio" name="is_rental" id="is_rental_off" value="0" {if $is_rental == 0}checked="checked"{/if}>
            <label for="is_rental_off">{l s='Non' mod='rentalroute'}</label>
            <a class="slide-button btn"></a>
        </span>
    </div>
</div>
<div class="form-group">
    <label class="control-label col-lg-3">{l s='Prix par mois (12 mois)' mod='rentalroute'}</label>
    <div class="col-lg-2">
        {* Assurez-vous que le nom est bien 'price_per_month_12' *}
        <input type="text" name="price_per_month_12" value="{Tools::ps_round($price_per_month_12, 2)}">
    </div>
</div>

<div class="form-group">
    <label class="control-label col-lg-3">{l s='Prix par mois (36 mois)' mod='rentalroute'}</label>
    <div class="col-lg-2">
        {* Assurez-vous que le nom est bien 'price_per_month_36' *}
        <input type="text" name="price_per_month_36" value="{Tools::ps_round($price_per_month_36, 2)}">
    </div>
</div>

<div class="form-group">
    <label class="control-label col-lg-3">{l s='Montant du dépôt (€)' mod='rentalroute'}</label>
    <div class="col-lg-2">
        <input type="text" name="deposit_amount" value="{Tools::ps_round($deposit_amount, 2)}">
    </div>
</div>

<div class="form-group">
    <label class="control-label col-lg-3">{l s='Frais d\'installation (€)' mod='rentalroute'}</label>
    <div class="col-lg-2">
        <input type="text" name="installation_fee" value="{Tools::ps_round($installation_fee, 2)}">
    </div>
</div>