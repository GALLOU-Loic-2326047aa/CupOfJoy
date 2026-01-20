{extends file="page.tpl"}

{block name="page_title"}
    <h1>{$page_title}</h1>
{/block}
{block name="page_content"}
    <form method="post" action="{$link->getModuleLink('rentFunnel', 'saveChoice')}">
        <div class="rentFunnel-product-list">
            {foreach from=$products item=product}
                <div class="rentFunnel-product-item">
                    <img src="{$shop_url}{$product.image_url}"
                         alt="{$product.name}"
                         style="max-width: 300px; max-height: 300px; width: auto; height: auto;"/>
                    <p>{$product.description nofilter}</p>
                    <span>
                    Acheter ce produit pour {$product.price|rtrim:'0'|rtrim:'.'}{$shop_currency} l'unité.
                </span>
                    <div>
                        <label for="qty_{$product.id_product}">Quantité :</label>
                        <div class="quantity-button js-quantity-button ">
                            <div class="input-group flex-nowrap product-select-button">

                                <input id="quantity_wanted_{$product.id_product}" value="0" min="0" class="form-control" name="product_quantities[{$product.id_product}]"
                                       aria-label="Quantité" type="text" inputmode="numeric" pattern="[0-9]*">
                                
                            </div>
                        </div>
                        <input type="hidden" name="product_info[{$product.id_product}][name]" value="{$product.name|escape:'html'}" />
                        <input type="hidden" name="product_info[{$product.id_product}][description]" value="{$product.description|strip_tags|escape:'html'}" />
                        <input type="hidden" name="product_info[{$product.id_product}][price]" value="{$product.price}" />
                    </div>
                </div>
            {/foreach}
        </div>
        <button type="submit" class="btn btn-primary">
            Valider ma sélection
        </button>
        {if $categoryList[0]->skippable == 1}
            {include file="module:rentFunnel/views/templates/front/chooseProductPass.tpl"}
        {/if}
    </form>
{/block}
