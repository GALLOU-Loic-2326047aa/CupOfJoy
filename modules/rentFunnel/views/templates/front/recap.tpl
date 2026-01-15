{extends file="page.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}

{block name="page_content"}
    {foreach from=$category_list item=category}
        {if $category.name == 'Produit loué'}
            <h3>{$category.name}</h3>
        {else}
            <h3>Catégorie {$category.name}</h3>
        {/if}
        {foreach from=$category.products item=product}
            <h4>{$product.name}</h4>
            <p>{$product.description}</p>
            {if isset($product.rental_duration)}
                <p>Prix par mois : {$product.price|rtrim: '0'|rtrim: '.'}{$shop_currency}</p>
                <p>Durée : {$product.rental_duration} mois</p>
            {else}
                <p>Prix du produit : {$product.price|rtrim: '0'|rtrim: '.'}{$shop_currency}</p>
                {if isset($product.quantity)}
                    <p>Quantité choisie : {$product.quantity}</p>
                    <p style="font-weight: bold">Prix total : {$product.price * $product.quantity}{$shop_currency}</p>
                {/if}
            {/if}
        {/foreach}
        <hr>

    {/foreach}

    <hr>
    <div class="total-section">
        <h3>Prix total de la commande : {$total_price|string_format:"%.2f"}{$shop_currency}</h3>
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