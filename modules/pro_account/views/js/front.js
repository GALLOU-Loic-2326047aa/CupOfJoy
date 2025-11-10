document.addEventListener('DOMContentLoaded', function() {
    const isProCheckbox = document.querySelector('input[name="is_pro"]');

    if (!isProCheckbox) {
        return;
    }

    const proFields = document.querySelectorAll('.pro-field');

    function toggleProFields() {
        proFields.forEach(function(fieldInput) {
            const formGroup = fieldInput.closest('.form-group');
            if (formGroup) {
                if (isProCheckbox.checked) {
                    formGroup.style.display = 'block';
                } else {
                    formGroup.style.display = 'none';
                }
            }
        });
    }

    toggleProFields();
    isProCheckbox.addEventListener('change', toggleProFields);
});