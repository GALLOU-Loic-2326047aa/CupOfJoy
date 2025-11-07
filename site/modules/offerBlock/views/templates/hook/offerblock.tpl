<div>
    {if isset($offerBlock)}
        <div class="offer_block">
            {$offerBlock->name}
            {$offerBlock->image}
        </div>
    {else}
        erreur
    {/if}
</div>