{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Mes abonnements' mod='ps_stripe_subscriptions'}
{/block}

{block name='page_content'}

    {if isset($stripe_error) && $stripe_error}
        <article class="alert alert-danger" role="alert" data-alert="danger">
            <ul>
                <li>{$stripe_error}</li>
            </ul>
        </article>
    {/if}

    <h6>{l s='Retrouvez ici la gestion de vos abonnements récurrents.' mod='ps_stripe_subscriptions'}</h6>

    {if $subscriptions && count($subscriptions) > 0}
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="thead-default">
                <tr>
                    <th>{l s='Référence' mod='ps_stripe_subscriptions'}</th>
                    <th>{l s='Montant' mod='ps_stripe_subscriptions'}</th>
                    <th>{l s='Statut' mod='ps_stripe_subscriptions'}</th>
                    <th>{l s='Prochaine échéance' mod='ps_stripe_subscriptions'}</th>
                    <th class="text-sm-center">{l s='Actions' mod='ps_stripe_subscriptions'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$subscriptions item=sub}
                    <tr>
                        <td>
                            <strong>{$sub.id|truncate:15:'...'}</strong><br>
                            <small class="text-muted">{$sub.plan.product.name|default:'Abonnement'}</small>
                        </td>
                        <td>
                            {($sub.plan.amount / 100)|number_format:2:',':' '} {$sub.plan.currency|upper}
                            <small> / {$sub.plan.interval}</small>
                        </td>
                        <td>
                            {if $sub.status == 'active'}
                                <span class="label label-success badge-success">{l s='Actif' mod='ps_stripe_subscriptions'}</span>
                            {elseif $sub.status == 'past_due'}
                                <span class="label label-danger badge-danger">{l s='Défaut de paiement' mod='ps_stripe_subscriptions'}</span>
                            {else}
                                <span class="label label-default badge-default">{$sub.status}</span>
                            {/if}
                        </td>
                        <td>
                            {$sub.current_period_end|date_format:"%d/%m/%Y"}
                        </td>
                        <td class="text-sm-center">
                            {if $sub.cancel_at_period_end}
                                <span class="badge badge-warning">
                    <i class="material-icons">timer</i> {l s='Fin prévue' mod='ps_stripe_subscriptions'}
                  </span>
                            {else}
                                <a href="{$link->getModuleLink('ps_stripe_subscriptions', 'cancel', ['id_sub' => $sub.id])}"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('{l s='Voulez-vous vraiment résilier cet abonnement ?' mod='ps_stripe_subscriptions'}');">
                                    <i class="material-icons">cancel</i> {l s='Résilier' mod='ps_stripe_subscriptions'}
                                </a>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <div class="alert alert-info" role="alert" data-alert="info">
            {l s='Vous n\'avez aucun abonnement actif pour le moment.' mod='ps_stripe_subscriptions'}
        </div>
    {/if}

    <footer class="page-footer">
        <a href="{$my_account_url}" class="account-link">
            <i class="material-icons">&#xE5CB;</i>
            <span>{l s='Retour à votre compte' mod='ps_stripe_subscriptions'}</span>
        </a>
    </footer>

{/block}