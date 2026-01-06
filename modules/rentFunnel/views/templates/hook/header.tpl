<div id="recap-container" class="recap-container">
    <button id="btn-recap" class="btn-recap" type="button">
        <span>
            Mon abonnement
        </span>
        <i class="material-icons header-block__icon" aria-hidden="true">shopping_cart</i>
    </button>
    <div id="recap-panel" class="recap-details" style="display: none;">
        {foreach from=$category_list key=categoryName item=category}
            {if $category.name == 'Produit loué'}
            <span class="header-recap-category">{$category.name} : </span>
            {else}
            <div>
                <span class="header-recap-category">{$category.name} choisi(e)(s) : </span>
            </div>
            {/if}
            {foreach from=$category.products item=product}
                <p class="header-recap-product">{$product.name}</p>
                {if isset($product.quantity)}
                    <p>Quantité choisie : {$product.quantity}</p>
                {/if}
            {/foreach}
        {/foreach}
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btn-recap');
        const panel = document.getElementById('recap-panel');

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });

        document.addEventListener('click', function () {
            panel.style.display = 'none';
        });

        panel.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
</script>