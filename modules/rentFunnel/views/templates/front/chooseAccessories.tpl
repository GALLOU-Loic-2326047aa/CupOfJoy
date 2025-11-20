{extends file="page.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}
{block name="page_content"}
    <ul class="rentFunnel-product-list">
        {foreach from=$accessories item=accessory}
            <li class="rentFunnel-product-item">
                <img src="{$shop_url}{$accessory.image_url}"
                     alt="{$accessory.name}"
                     style="max-width: 300px; max-height: 300px; width: auto; height: auto;"/>
                <p>{$accessory.description nofilter}</p>
                <button class="btn btn-primary" type="button"
                        onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'saveChoice')}?accessory_id={$accessory.id_product}&accessory_name={$accessory.name|urlencode}&accessory_name={$accessory.price}'">
                    Acheter cet accessoire pour {$accessory.price|rtrim: '0'|rtrim: '.'}{$shop_currency}
                </button>
            </li>
        {/foreach}
    </ul>
{/block}
{block name="page_footer"}
    <button class="btn btn-primary" type="button" onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'recap')}'">
        Passer cette étape
    </button>
{/block}