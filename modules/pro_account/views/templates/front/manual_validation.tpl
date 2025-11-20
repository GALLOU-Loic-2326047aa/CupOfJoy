{extends file='page.tpl'}

{block name='page_title'}
    {l s='Demande de validation manuelle' mod='pro_account'}
{/block}

{block name='page_content'}
    <div id="manual-siret-validation-page">

        {include file='_partials/notifications.tpl'}

        {if !$confirmation}
            <p>{l s='Votre numéro de SIRET n\'est pas reconnu ? Pas d\'inquiétude. Veuillez remplir le formulaire ci-dessous et nous procéderons à une vérification manuelle.' mod='pro_account'}</p>

            <form method="post" class="form">
                <section>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label required">{l s='Prénom' mod='pro_account'}</label>
                        <div class="col-md-6">
                            <input type="text" name="firstname" class="form-control" value="{if isset($smarty.post.firstname)}{$smarty.post.firstname|escape:'htmlall':'UTF-8'}{/if}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label required">{l s='Nom' mod='pro_account'}</label>
                        <div class="col-md-6">
                            <input type="text" name="lastname" class="form-control" value="{if isset($smarty.post.lastname)}{$smarty.post.lastname|escape:'htmlall':'UTF-8'}{/if}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label required">{l s='Email' mod='pro_account'}</label>
                        <div class="col-md-6">
                            <input type="email" name="email" class="form-control" value="{if isset($smarty.post.email)}{$smarty.post.email|escape:'htmlall':'UTF-8'}{/if}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label required">{l s='Nom de l\'entreprise' mod='pro_account'}</label>
                        <div class="col-md-6">
                            <input type="text" name="company_name" class="form-control" value="{if isset($smarty.post.company_name)}{$smarty.post.company_name|escape:'htmlall':'UTF-8'}{/if}" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 form-control-label required">{l s='Numéro de SIRET' mod='pro_account'}</label>
                        <div class="col-md-6">
                            <input type="text" name="siret" class="form-control" value="{if isset($smarty.post.siret)}{$smarty.post.siret|escape:'htmlall':'UTF-8'}{/if}" required maxlength="14">
                        </div>
                    </div>
                </section>

                <footer class="form-footer text-center">
                    <button type="submit" name="submitManualSiret" class="btn btn-primary">
                        {l s='Envoyer la demande' mod='pro_account'}
                    </button>
                </footer>
            </form>
        {/if}
    </div>
{/block}