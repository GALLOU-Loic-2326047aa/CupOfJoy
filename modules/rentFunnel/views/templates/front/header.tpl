{extends file="page.tpl"}

{block name="header"}
    <div>
        <strong class="dark">Machine choisie :</strong>
        {if isset($machine_id)}
        <div>{$machine_name}</div>
        {/if}
    </div>
    <div>
        <strong class="dark">Café choisi :</strong>
        {if isset($coffee_id)}
        <div>{$coffee_name}</div>
        {/if}
    </div>
    <div>
        <strong class="dark">Accessoire choisi :</strong>
        {if isset($accessory_id)}
            <div>{$accessory_name}</div>
        {/if}
    </div>
{/block}