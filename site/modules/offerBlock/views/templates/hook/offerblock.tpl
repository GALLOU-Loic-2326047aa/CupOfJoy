<div>
    {if isset($offerBlock)}
        <div class="offer_block">
            <div>
                <h3>{$offerBlock->name}</h3>
            {if $offerBlock->image}
                <img src="{$urls.base_url}modules/offerBlock/img/{$offerBlock->image}" alt="{$offerBlock->name}" />
            {/if}
            </div>

            <div class="products">
                {* Vous pouvez accéder aux Images des produits *}
                <img src="{$offerBlock->productImages[1]}" alt="Product 1 Image">
                <img src="{$offerBlock->productImages[2]}" alt="Product 2 Image">
                <img src="{$offerBlock->productImages[3]}" alt="Product 3 Image">
                <img src="{$offerBlock->productImages[4]}" alt="Product 4 Image">
            </div>
        </div>
    {else}
        erreur
    {/if}
</div>