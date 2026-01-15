<div class="component-line form-group">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3">
                <label class="form-control-label">
                    <i class="material-icons">refresh</i> {l s='Abonnement Stripe' mod='ps_stripe_subscriptions'}
                </label>
            </div>
            <div class="col-md-9">
                <div class="input-group">
                    <span class="ps-switch">
                        <input type="radio"
                               name="is_stripe_subscription_{$id_combination}"
                               id="is_stripe_subscription_{$id_combination}_off"
                               value="0"
                               {if !$is_stripe_subscription}checked="checked"{/if}>
                        <label for="is_stripe_subscription_{$id_combination}_off">{l s='Non (Achat unique)' mod='ps_stripe_subscriptions'}</label>

                        <input type="radio"
                               name="is_stripe_subscription_{$id_combination}"
                               id="is_stripe_subscription_{$id_combination}_on"
                               value="1"
                               {if $is_stripe_subscription}checked="checked"{/if}>
                        <label for="is_stripe_subscription_{$id_combination}_on">{l s='Oui (Mensuel)' mod='ps_stripe_subscriptions'}</label>

                        <span class="slide-button"></span>
                    </span>
                </div>
                <p class="help-block">
                    {l s='Si activé, cette déclinaison spécifique sera traitée comme un abonnement récurrent sur Stripe.' mod='ps_stripe_subscriptions'}
                </p>
            </div>
        </div>
    </div>
</div>
<hr/>