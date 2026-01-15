// Fonction pour afficher les options des categories lorsqu'elles sont sélectionnées, dans le premier formulaire
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

// Fonction pour ajouter les options d'un nouveau menu déroulant, dans le deuxième formulaire
$(document).ready(function() {
    let dropdownCounter = 0;

    // Fonction pour créer un menu déroulant
    function createDropdownField(index, data) {

        const questionType    = data?.question_type    || '';
        const questionCategory   = data?.question_category   || '';

        return `
        <div class="form-group row dropdown-group" data-index="${index}">
            <label class="col-lg-3 col-form-label required">
                Menu déroulant #${index + 1}
            </label>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-md-12">
                        <label>Type de question</label>
                        <select class="form-control" name="dropdown_question_type_${index}">
                            <option value="preference" ${questionType === 'preference' ? 'selected' : ''}>Préférence</option>
                            <option value="consumption" ${questionType === 'consumption' ? 'selected' : ''}>Consommation</option>
                        </select>
                        
                        <label>Pour quelle catégorie ?</label>
                        <select class="form-control" name="dropdown_categories_${index}">
                            <!-- Options remplies par JS -->
                        </select>
                    </div>
                    
                    <div class="col-md-1">
                        <button type="button"
                                class="btn btn-danger btn-block remove-dropdown"
                                data-index="${index}">
                                <i class="icon-trash"></i>
                        </button>
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

    const categories = container && container.dataset.categories ? JSON.parse(container.dataset.categories) : [];

    function populateCategorySelects() {
        $('.dropdown-group select[name^="dropdown_categories_"]').each(function() {
            const $select = $(this);

            categories.forEach(function(cat) {
                $select.append(`
                <option value="${cat.id_category}">${cat.name}</option>
            `);
            });
        });
    }

    populateCategorySelects();

    $('#add-dropdown-btn').click(function() {
        const html = createDropdownField(dropdownCounter, null);
        $('#dropdown-list').append(html);
        dropdownCounter++;
        populateCategorySelects();
    })

    $(document).on('click', '.remove-dropdown', function(){
        $(this).closest('.dropdown-group').remove();
    })
});