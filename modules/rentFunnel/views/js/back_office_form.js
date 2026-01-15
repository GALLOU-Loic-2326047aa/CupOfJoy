document.addEventListener('DOMContentLoaded', function() {
    const categories = new Set();

    document.querySelectorAll('input[type="radio"][name^="category_"][id$="_on"]')
        .forEach(function(input) {
        const match = input.name.match(/^category_(\d+)$/);
        if (match) {
            categories.add(match[1]);
        }
    })

    categories.forEach(function(categoryId) {
        const switchOn = document.getElementById('category_' + categoryId + '_on');
        const switchOff = document.getElementById('category_' + categoryId + '_off');

        if (!switchOn || !switchOff) return;

        const childElements = document.querySelectorAll('[class*="toggle-child-category-' + categoryId + '"]')

        const toggleFields = function () {
            const isEnabled = switchOn.checked;
            childElements.forEach(function(el) {
                el.style.display = isEnabled ? '' : 'none';
            });
        };

        toggleFields();

        switchOn.addEventListener('change', toggleFields);
        switchOff.addEventListener('change', toggleFields);
    })
});
