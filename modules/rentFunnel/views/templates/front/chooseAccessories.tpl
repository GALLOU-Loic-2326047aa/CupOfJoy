{extends file="page.tpl"}
{include file="module:rentFunnel/views/templates/front/header.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}
{block name="page_content"}
    <ul>
        {foreach from=$accessories item=accessory}
            <li>
                <img src="{$shop_url}{$accessory.image_url}"
                     alt="{$accessory.name}"
                     style="max-width: 300px; max-height: 300px; width: auto; height: auto;"/>
                <p>{$accessory.description nofilter}</p>
                <button class="btn btn-primary" type="button"
                        onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'saveChoice')}?accessory_id={$accessory.id_product}&accessory_name={$accessory.name|urlencode}'">
                    Louer cet accessoire
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