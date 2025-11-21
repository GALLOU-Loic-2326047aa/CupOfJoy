{extends file="page.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}

{block name="page_content"}
    {foreach from=$category_list item=category}
        <h3>Catégorie {$category}</h3>
        {foreach from=$product_list item=product}
            <h4>{$product.name}</h4>
            <p>{$product.description}</p>
            <p>Prix du produit : {$product.price}{$shop_currency}</p>
            {if isset($product.quantity)}
                <p>Quantité choisie : {$product.quantity}</p>
                <p>Prix total : {$product.price * $product.quantity}{$shop_currency}</p>
            {/if}
        {/foreach}
    {/foreach}
{/block}