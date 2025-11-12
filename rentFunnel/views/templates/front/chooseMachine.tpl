{extends file="page.tpl"}

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
        </li>
    {/foreach}
</ul>
{/block}