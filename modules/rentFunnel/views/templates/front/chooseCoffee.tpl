{extends file="page.tpl"}
{include file="module:rentFunnel/views/templates/front/header.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}
{block name="page_content"}
    <ul>
        {foreach from=$coffees item=coffee}
            <li>
                <img src="{$shop_url}{$coffee.image_url}"
                     alt="{$coffee.name}"
                     style="max-width: 300px; max-height: 300px; width: auto; height: auto;"/>
                <p>{$coffee.description nofilter}</p>
                <button class="btn btn-primary" type="button"
                        onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'saveChoice')}?coffee_id={$coffee.id_product}&coffee_name={$coffee.name|urlencode}'">
                    Louer ce café.
                </button>
            </li>
        {/foreach}
    </ul>
{/block}