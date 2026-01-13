<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addToCartBlock = document.querySelector('.add-to-cart');
        const quantity = document.querySelector('.qty')
        const labelQty = document.querySelector('.control-label');
        if (addToCartBlock) {
            addToCartBlock.remove();
            quantity.remove();
            labelQty.remove();
        }
    });
</script>