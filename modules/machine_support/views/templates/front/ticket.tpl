{extends file='page.tpl'}

{block name='page_title'}
    {l s='Déclarer un problème' mod='machine_support'}
{/block}

{block name='page_content'}
    <div class="machine-support-form">

        {if isset($success) && $success}
            <div class="alert alert-success">
                {foreach from=$success item=msg}{$msg}<br>{/foreach}
            </div>
        {/if}

        <form action="{$link->getModuleLink('machine_support', 'ticket')}" method="post">

            <section class="form-fields">

                {* EMAIL *}
                <div class="form-group row">
                    <label class="col-md-3 form-control-label required">
                        {l s='Adresse Email' mod='machine_support'}
                    </label>
                    <div class="col-md-6">
                        <input
                                class="form-control"
                                name="from"
                                type="email"
                                value="{if $customer.is_logged}{$customer.email}{/if}"
                                {if $customer.is_logged}readonly{/if}
                                required
                        >
                    </div>
                </div>

                {* TÉLÉPHONE *}
                <div class="form-group row">
                    <label class="col-md-3 form-control-label required">
                        {l s='Numéro de téléphone' mod='machine_support'}
                    </label>
                    <div class="col-md-6">
                        <input class="form-control" name="phone" type="tel" required>
                    </div>
                </div>

                {* TYPE DE DEMANDE *}
                <div class="form-group row">
                    <label class="col-md-3 form-control-label required">
                        {l s='Type de demande' mod='machine_support'}
                    </label>
                    <div class="col-md-6">
                        <select class="form-control" name="request_type" required>
                            <option value="">-- {l s='Choisir le motif' mod='machine_support'} --</option>
                            <option value="Panne Machine">{l s='Panne / Problème technique' mod='machine_support'}</option>
                            <option value="Intervention">{l s='Demande d\'intervention' mod='machine_support'}</option>
                            <option value="Remboursement">{l s='Demande de remboursement' mod='machine_support'}</option>
                            <option value="Autre">{l s='Autre demande' mod='machine_support'}</option>
                        </select>
                    </div>
                </div>

                {* MESSAGE *}
                <div class="form-group row">
                    <label class="col-md-3 form-control-label required">
                        {l s='Description détaillée' mod='machine_support'}
                    </label>
                    <div class="col-md-9">
                        <textarea class="form-control" name="message" rows="5" placeholder="{l s='Décrivez le problème...' mod='machine_support'}" required></textarea>
                    </div>
                </div>

            </section>

            <footer class="form-footer text-center">
                <button type="submit" name="submitMachineSupport" class="btn btn-primary">
                    {l s='Envoyer la demande' mod='machine_support'}
                </button>
            </footer>

        </form>
    </div>
{/block}