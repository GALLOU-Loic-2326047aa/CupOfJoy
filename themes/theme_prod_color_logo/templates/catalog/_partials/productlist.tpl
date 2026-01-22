<div class="nespresso-grid">
    {foreach from=$products item="product"}
        {include file="catalog/_partials/miniatures/product.tpl" product=$product}
    {/foreach}
</div>