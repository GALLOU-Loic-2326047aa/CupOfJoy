<div>
    {if isset($offerBlock)}
        <div class="offer_block">
            <h3>{$offerBlock->name}</h3>
            {if $offerBlock->image}
                <img src="{$urls.base_url}modules/offerBlock/img/{$offerBlock->image}" alt="{$offerBlock->name}" />
            {/if}

            <div class="products">
                {* Vous pouvez accéder aux IDs des produits *}
                Product 1 ID: {$offerBlock->product1_id}<br>
                Product 2 ID: {$offerBlock->product2_id}<br>
                Product 3 ID: {$offerBlock->product3_id}<br>
                Product 4 ID: {$offerBlock->product4_id}<br>
            </div>
        </div>
    {else}
        erreur
    {/if}
</div>