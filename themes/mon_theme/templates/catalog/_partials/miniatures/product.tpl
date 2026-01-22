<article class="nespresso-card js-product-miniature" data-id-product="{$product.id_product}">
    <div class="card-img-block">
        <a href="{$product.url}">
            <img src="{$product.cover.bySize.home_default.url}" alt="{$product.cover.legend}">
        </a>

        {* Affichage du badge de réduction *}
        {if $product.has_discount}
            <span class="discount-pill">
                {if $product.discount_type === 'percentage'}{$product.discount_percentage}{else}-{$product.discount_amount_to_display}{/if}
            </span>
        {/if}
    </div>

    <div class="card-content">
        <h3 class="product-title"><a href="{$product.url}">{$product.name|truncate:35:'...'}</a></h3>
        <p class="product-desc">{$product.description_short|strip_tags:'UTF-8'|truncate:60:'...'}</p>

        <div class="card-footer">
            <div class="price-section">
                {if $product.has_discount}
                    <span class="regular-price">{$product.regular_price}</span>
                {/if}
                <span class="current-price">{$product.price}</span>
            </div>

            <form action="{$urls.pages.cart}" method="post">
                <input type="hidden" name="token" value="{$static_token}">
                <input type="hidden" name="id_product" value="{$product.id}">
                <button class="add-to-cart-green" data-button-action="add-to-cart" type="submit">
                    <i class="material-icons">add</i>
                </button>
            </form>
        </div>
    </div>
</article>