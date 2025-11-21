{extends file='page.tpl'}

{block name='page_content'}
    <section id="content" class="page-content page-banned">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="h1">
                        {l s='Trop de requêtes' d='Modules.RateLimiter.Shop'}
                    </h1>
                    <p>
                        <b>{l s='Veuillez patienter quelques instants avant de réessayer.' d='Modules.RateLimiter.Shop'}</b>
                    </p>
                    <a href="{$urls.base_url}" class="btn btn-primary mt-3">
                        {l s='Retour à l\'accueil' d='Shop.Theme.Actions'}
                    </a>
                </div>
            </div>
        </div>
    </section>
{/block}