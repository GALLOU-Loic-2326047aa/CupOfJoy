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

$(document).ready(function() {
    let dropdownCounter = 0;

    // Fonction pour créer un menu déroulant
    function createDropdownField(index, data) {

        const name    = data?.name    || '';
        const label   = data?.label   || '';
        const defaultVal = data?.default || '';
        const options = (data?.options || []).join('\n');

        return `
        <div class="form-group row dropdown-group" data-index="${index}">
            <label class="col-lg-3 col-form-label required">
                Menu déroulant #${index + 1}
            </label>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" 
                               class="form-control" 
                               name="dropdown_name_${index}" 
                               placeholder="Nom du champ (name)"
                               value="${name.replace(/"/g, '&quot;')}"
                               required>
                    </div>
                    <div class="col-md-5">
                        <input type="text"
                                class="form-control"
                                name="dropdown_label_${index}"
                                value="${label.replace(/"/g, '&quot;')})"
                                placeholder="Libellé affiché">
                    </div>
                    <div class="col-md-3">
                        <input type="text"
                                class="form-control"
                                name="dropdown_default_${index}"
                                value="${defaultVal.replace(/"/g, '&quot;')}"
                                placeholder="Valeur par défaut">
                    </div>
                    <div class="col-md-1">
                        <button type="button"
                                class="btn btn-danger btn-block remove-dropdown"
                                data-index="${index}">
                                <i class="icon-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <textarea class="form-control"
                                    name="dropdown_options_${index}"
                                    rows="3"
                                    placeholder="Options (une par ligne):&#10;option1&#10;option2&#10;option3">${options}</textarea>
                    </div>
                </div>
            </div>
        </div>`
    }

    const container = document.getElementById('dropdown_container');
    let savedDropdowns = [];
    if (container && container.dataset.dropdowns) {
        try {
            savedDropdowns = JSON.parse(container.dataset.dropdowns);
        } catch (e) {
            console.error('Erreur lors du parsing JSON:', e);
            savedDropdowns = [];
        }
    }

    savedDropdowns.forEach(function(dropdown, i) {
        const html = createDropdownField(i, dropdown);
        $('#dropdown-list').append(html);
        dropdownCounter = i + 1;
    });

    $('#add-dropdown-btn').click(function() {
        const html = createDropdownField(dropdownCounter, null);
        $('#dropdown-list').append(html);
        dropdownCounter++;
    })

    $(document).on('click', '.remove-dropdown', function(){
        $(this).closest('.dropdown-group').remove();
    })
})