document.addEventListener('DOMContentLoaded', function() {
    // Trouve tous les switches "Activer ?" et gère leur comportement
    const switches = document.querySelectorAll('input[type="checkbox"][name^="category_"][value="1"]');

    switches.forEach(function(switchInput) {
        // Extrait l'ID de la catégorie du name (ex: category_2 → 2)
        const categoryId = switchInput.name.replace('category_', '');

        // Trouve tous les éléments enfants pour cette catégorie
        const childElements = document.querySelectorAll('[class*="toggle-child-category-' + categoryId + '"]');

        // Fonction de bascule
        const toggleFields = function() {
            const isChecked = switchInput.checked;
            childElements.forEach(function(el) {
                el.style.display = isChecked ? '' : 'none';
            });
        };

        // Initialise l'état actuel
        toggleFields();

        // Écoute les changements
        switchInput.addEventListener('change', toggleFields);
    });
});
