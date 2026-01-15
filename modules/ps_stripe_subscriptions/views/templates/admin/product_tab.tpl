<div class="m-t-2">
    <h2>Stripe Subscriptions</h2>
    <div class="form-group">
        <label class="control-label">Activer comme abonnement ?</label>
        <div class="col-lg-9">
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="is_stripe_subscription" id="is_stripe_subscription_on" value="1" {if $is_stripe_subscription}checked="checked"{/if}>
                <label for="is_stripe_subscription_on">Oui</label>
                <input type="radio" name="is_stripe_subscription" id="is_stripe_subscription_off" value="0" {if !$is_stripe_subscription}checked="checked"{/if}>
                <label for="is_stripe_subscription_off">Non</label>
                <a class="slide-button btn"></a>
            </span>
            <p class="help-block">Si activé, ce produit sera traité comme un paiement récurrent mensuel via Stripe.</p>
        </div>
    </div>
</div>