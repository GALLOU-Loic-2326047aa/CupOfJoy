{extends file="page.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}

{block name="page_content"}
    <div>
        Machine choisie : {$machine_name} -> {$machine_price}{$shop_currency}
    </div>
    <div>
        Cafés choisis :
        {foreach from=$coffees item=coffee}
            {$coffee.name} -> {$coffee.price}{$shop_currency}
        {/foreach}
    </div>
    <div>
        Accessoire choisi :
        {if !is_bool($accessory_name)}
            {$accessory_name} -> {$accessory_price}{$shop_currency}
        {else}
            aucun -> 0{$shop_currency}
        {/if}
    </div>
    <div>
        Prix total : {$total_price}{$shop_currency}
    </div>
{/block}