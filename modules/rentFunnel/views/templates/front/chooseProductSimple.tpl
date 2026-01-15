{extends file="page.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}
{block name="page_content"}
    {if $categoryList[0]->skippable == 1}
        {include file="module:rentFunnel/views/templates/front/chooseProductPass.tpl"}
    {/if}
    <ul class="rentFunnel-product-list">
        {foreach from=$products item=product}

            <li class="rentFunnel-product-item">
                <img src="{$shop_url}{$product.image_url}"
                 alt="{$product.name}"
                 style="max-width: 300px; max-height: 300px; width: auto; height: auto;"/>
                <p>{$product.description nofilter}</p>
                <button class="btn btn-primary btn-rent-funnel" type="button"
                        onclick="window.location.href='{$link->getModuleLink('rentFunnel', 'saveChoice')}?product_id={$product.id_product}'">
                    Louer "{$product.name}" pour {$product.price|rtrim: '0'|rtrim: '.'}{$shop_currency}
                </button>
            </li>
        {/foreach}
    </ul>
{/block}