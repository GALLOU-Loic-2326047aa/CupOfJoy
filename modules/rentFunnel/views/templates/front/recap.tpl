{extends file="page.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}

{block name="page_content"}
    <div class="recap-container">
    {foreach from=$category_list item=category}
        <div class="recap-section">
            <div class="section-header">
                <div class="section-title">
                    <h2>{$category.name} <span class="count">({$category.products|count})</span> </h2>
                </div>
            </div>

            <div class="table-header">
                <span></span>
                <span>Prix unitaire</span>
                <span>Quantité</span>
                <span>Total</span>
            </div>

            {foreach from=$category.products item=product}
                <div class="product-row">
                    <div class="product-info">
                        <img src="{$shop_url}{$product.image_url}"
                             alt="{$product.name}"
                             class="img-fluid recap-img"/>
                        <span class="product-name">{$product.name}</span>
                    </div>
                    <div class="product-price">
                        {$product.price|rtrim:'0'|rtrim:'.'}{$shop_currency}
                    </div>
                    <div class="product-quantity">
                        {if isset($product.quantity)}
                            x{$product.quantity}
                        {else}
                            x1
                        {/if}
                    </div>
                    <div class="product-total">
                        {if isset($product.quantity)}
                            {($product.price * $product.quantity)|string_format:"%.2f"}{$shop_currency}
                        {else}
                            {$product.price|rtrim:'0'|rtrim:'.'}{$shop_currency}
                        {/if}
                    </div>
                </div>
            {/foreach}
        </div>
    {/foreach}

    <div class="total-section">
        <h3>Prix total : {$total_price|string_format:"%.2f"}{$shop_currency}</h3>
    </div>

    <div class="actions-section">
        <form method="post" action="{$link->getModuleLink('rentFunnel', 'recap')}">
            <button type="submit" name="addToCart" class="btn btn-primary btn-lg">
                <i class="material-icons shopping-cart">shopping_cart</i>
                Ajouter cette formule au panier
            </button>
        </form>

        <a href="{$link->getPageLink('index')}" class="btn btn-secondary">
            Retour à l'accueil
        </a>
    </div>
{/block}