<div class="footer-container">
    <div class="container">
        {* 1. Barre de Réassurance (Livraison, Paiement, etc.) *}
        <div class="footer-reassurance-row">
            <div class="reassurance-item">
                <i class="material-icons">local_shipping</i>
                <span>{l s='Livraison Express' d='Shop.Theme.Global'}</span>
            </div>
            <div class="reassurance-item">
                <i class="material-icons">verified_user</i>
                <span>{l s='Paiement Sécurisé' d='Shop.Theme.Global'}</span>
            </div>
            <div class="reassurance-item">
                <i class="material-icons">autorenew</i>
                <span>{l s='Retour Facile' d='Shop.Theme.Global'}</span>
            </div>
        </div>

        {* 2. Zone des Liens (Hooks PrestaShop) *}
        <div class="row footer-links-section">
            {block name='hook_footer'}
                {hook h='displayFooter'}
            {/block}
        </div>

        {* 3. Bas de page / Copyright *}
        <div class="footer-bottom">
            <div class="copyright">
                {block name='copyright_link'}
                    <a class="_blank" href="https://www.prestashop.com" target="_blank">
                        {l s='%copyright% %year% - Boutique réalisée par %prestashop%' sprintf=['%prestashop%' => 'PrestaShop™', '%year%' => 'Y'|date, '%copyright%' => '©'] d='Shop.Theme.Global'}
                    </a>
                {/block}
            </div>
        </div>
    </div>
</div>