<section class="offer_block">
    {if isset($offerBlock)}
            <div>
                <h3>{$offerBlock->name}</h3>
            {if $offerBlock->image}
                <img src="{$urls.base_url}modules/offerBlock/img/{$offerBlock->image}" alt="{$offerBlock->name}" />
            {/if}
            </div>

            <section class="offer_block_products">
                {* Vous pouvez accéder aux Images des produits *}
                <div class="offer_block_row1">
                    <div>
                        <img src="{$offerBlock->productImages[1]}" alt="Product 1 Image">
                    </div>
                    <div>
                        <img src="{$offerBlock->productImages[2]}" alt="Product 2 Image">
                    </div>
                </div>
                <div class="offer_block_row2">
                    <div>
                        <img src="{$offerBlock->productImages[3]}" alt="Product 3 Image">
                    </div>
                    <div>
                        <img src="{$offerBlock->productImages[4]}" alt="Product 4 Image">
                    </div>
                </div>
            </section>
    {else}
        erreur
    {/if}
</section>