{block name='page_content_container'}
    <div class="card">
        <h1 class="h1 page-title">{l s='Gestion de mes abonnements' mod='ps_stripe_subscriptions'}</h1>

        {if isset($stripe_error)}
            <div class="alert alert-danger">{$stripe_error}</div>
        {else}
            <p>{l s='Votre compte Stripe ID:' mod='ps_stripe_subscriptions'} <strong>{$stripe_customer_id}</strong></p>

            <div class="alert alert-info">
                {l s='Statut actuel:' mod='ps_stripe_subscriptions'} <strong>{$subscription_status|escape:'htmlall':'UTF-8'}</strong>
            </div>

            <p>
                {l s='Prochaine facturation:' mod='ps_stripe_subscriptions'}
                <strong>{$subscription_next_bill|escape:'htmlall':'UTF-8'}</strong>
            </p>

            <a href="#" class="btn btn-primary">{l s='Mettre à jour ma carte' mod='ps_stripe_subscriptions'}</a>
            <a href="#" class="btn btn-secondary">{l s='Annuler l\'abonnement' mod='ps_stripe_subscriptions'}</a>
        {/if}
    </div>
{/block}