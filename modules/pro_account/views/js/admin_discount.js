$(document).ready(function() {

    // Gestion affichage
    function toggleScopeFields() {
        var scope = $('input[name="scope"]:checked').val();

        $('.product_select_container').hide();
        $('.category_tree_container').hide();

        if (scope === 'product') {
            $('.product_select_container').show();
        } else if (scope === 'category') {
            $('.category_tree_container').show();
        }
    }

    $('input[name="scope"]').change(toggleScopeFields);

    setTimeout(toggleScopeFields, 100);
});