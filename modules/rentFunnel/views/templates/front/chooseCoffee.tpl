{extends file="page.tpl"}

{block name="page_content"}
    <form method="post" action="{$link->getModuleLink('rentFunnel', 'saveChoice')}">
        <div class="rentFunnel-product-list">
            {foreach from=$coffees item=coffee}
                <div class="rentFunnel-product-item">
                    <img src="{$shop_url}{$coffee.image_url}"
                         alt="{$coffee.name}"
                         style="max-width: 300px; max-height: 300px; width: auto; height: auto;"/>
                    <p>{$coffee.description nofilter}</p>
                    <span>
                    Acheter ce café pour {$coffee.price|rtrim:'0'|rtrim:'.'}{$shop_currency}
                </span>
                    <div>
                        <label for="qty_{$coffee.id_product}">Quantité :</label>
                        <div class="quantity-button js-quantity-button ">
                            <div class="input-group flex-nowrap coffee-select-button">
                                <button role="button" aria-label="decrement" class="btn decrement js-decrement-button" type="button">
                                    <i class="material-icons" aria-hidden="true"></i>
                                    <i class="material-icons confirmation d-none"></i>
                                    <div class="spinner-border spinner-border-sm align-middle d-none"></div>
                                </button>
                                <input id="quantity_wanted_{$coffee.id_product}" value="0" min="0" class="form-control" name="qty"
                                       aria-label="Quantité" type="text" inputmode="numeric" pattern="[0-9]*">
                                <button role="button" aria-label="increment" class="btn increment js-increment-button" type="button">
                                    <i class="material-icons" aria-hidden="true"></i>
                                    <i class="material-icons confirmation d-none"></i>
                                    <div class="spinner-border spinner-border-sm align-middle d-none" role="status"></div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
        <button type="submit" class="btn btn-primary">
            Valider ma sélection
        </button>
    </form>
{/block}
