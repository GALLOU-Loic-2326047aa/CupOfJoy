{extends file='page.tpl'}

{block name='page_title'}
    {l s='Vérification en deux étapes' d='Modules.Multifacteurauthentification.Shop'}
{/block}

{block name='page_content'}
    <div class="mfa-verification-container">
        <p class="alert alert-info">
            {l s='Pour votre sécurité, nous vous avons envoyé un code de vérification à 6 chiffres par email. Veuillez le saisir ci-dessous.' d='Modules.Multifacteurauthentification.Shop'}
        </p>

        <form id="mfa-verification-form" action="{$form_action_url}" method="post">
            <section>
                <div class="form-group row justify-content-center">
                    <label class="col-md-3 form-control-label required" for="mfa_mail">
                        {l s='Code de vérification' d='Modules.Multifacteurauthentification.Shop'}
                    </label>
                    <div class="col-md-4">
                        <input id="mfa_mail" class="form-control" name="mfa_mail" type="text" inputmode="numeric" pattern="[0-9]{ldelim}6{rdelim}" required>
                    </div>
                </div>
            </section>

            <footer class="form-footer text-center">
                <button class="btn btn-primary" type="submit" name="submitMfaCode">
                    {l s='Valider' d='Modules.Multifacteurauthentification.Shop'}
                </button>
            </footer>
        </form>
    </div>
{/block}