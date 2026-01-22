<div class="images-container js-images-container">
    <div class="product-images-wrapper">

        {block name='product_images'}
            <div class="js-qv-mask mask product-images-vertical">
                <ul class="product-images js-qv-product-images">
                    {foreach from=$product.images item=image}
                        <li class="thumb-container js-thumb-container">
                            <img
                                    class="thumb js-thumb {if $image.id_image == $product.default_image.id_image} selected {/if}"
                                    data-image-medium-src="{$image.bySize.medium_default.url}"
                                    data-image-large-src="{$image.bySize.large_default.url}"
                                    src="{$image.bySize.small_default.url}"
                                    alt="{$image.legend}"
                                    title="{$image.legend}"
                                    width="100"
                                    itemprop="image"
                            >
                        </li>
                    {/foreach}
                </ul>
            </div>
        {/block}

        {block name='product_cover'}
            <div class="product-cover">

                {block name='product_flags'}
                    <ul class="product-flags js-product-flags">
                        {foreach from=$product.flags item=flag}
                            <li class="product-flag {$flag.type}">{$flag.label}</li>
                        {/foreach}
                    </ul>
                {/block}

                {if $product.default_image}
                    <img
                            class="js-qv-product-cover"
                            src="{$product.default_image.bySize.large_default.url}"
                            alt="{$product.default_image.legend}"
                            title="{$product.default_image.legend}"
                            style="width:100%;"
                            itemprop="image"
                    >
                    <div class="layer hidden-sm-down" data-toggle="modal" data-target="#product-modal">
                        <i class="material-icons zoom-in">search</i>
                    </div>
                {else}
                    <img src="{$urls.no_picture_image.bySize.large_default.url}" style="width:100%;">
                {/if}
            </div>
        {/block}

    </div>
</div>