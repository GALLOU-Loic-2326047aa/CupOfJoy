{extends file="page.tpl"}
{include file="module:rentFunnel/views/templates/front/header.tpl"}

{block name="page_title"}
<h1>{$page_title}</h1>
{/block}
{block name="page_content"}
<ul>
    {foreach from=$machines item=machine}

        <li>
            <img src="{$shop_url}{$machine.image_url}"
             alt="{$machine.name}"
             style="max-width: 300px; max-height: 300px; width: auto; height: auto;"/>
            <p>{$machine.description nofilter}</p>
            <button class="btn btn-primary" type="button"
                    onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'saveChoice')}?machine_id={$machine.id_product}&machine_name={$machine.name|urlencode}'">
                Louer cette machine
            </button>
        </li>
    {/foreach}
</ul>
{/block}