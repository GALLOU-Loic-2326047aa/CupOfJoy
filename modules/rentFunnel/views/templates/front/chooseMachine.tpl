{extends file="page.tpl"}

{block name="page_title"}
<h1>{$page_title}</h1>
{/block}
{block name="page_content"}
<ul class="rentFunnel-product-list">
    {foreach from=$machines item=machine}

        <li class="rentFunnel-product-item">
            <img src="{$shop_url}{$machine.image_url}"
             alt="{$machine.name}"
             style="max-width: 300px; max-height: 300px; width: auto; height: auto;"/>
            <p>{$machine.description nofilter}</p>
            <button class="btn btn-primary" type="button"
                    onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'saveChoice')}?machine_id={$machine.id_product}&machine_name={$machine.name|urlencode}&machine_price={$machine.price}'">
                Louer cette machine pour {$machine.price|rtrim: '0'|rtrim: '.'}{$shop_currency}
            </button>
        </li>
    {/foreach}
</ul>
{/block}