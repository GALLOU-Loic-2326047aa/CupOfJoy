{extends file='page.tpl'}

{block name='page_content_container'}
    <section id="content" class="page-home">

        {* 1. Bloc de Titre Stylisé *}
        <div class="section-title-wrapper">
            <h2 class="products-section-title">
                {l s='Nos meilleures sélections' d='Shop.Theme.Catalog'}
            </h2>
            <p class="section-subtitle">
                {l s='Découvrez l\'excellence de nos produits' d='Shop.Theme.Catalog'}
            </p>
        </div>

        {* 2. Bloc de Produits (Appelle productlist.tpl) *}
        {block name='page_content'}
            {block name='hook_home'}
                {$HOOK_HOME nofilter}
            {/block}
        {/block}

    </section>
{/block}