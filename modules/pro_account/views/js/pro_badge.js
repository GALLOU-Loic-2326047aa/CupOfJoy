document.addEventListener('DOMContentLoaded', function() {
    // La variable 'customerIsPro' est créée par notre hookDisplayHeader en PHP.
    // On vérifie si elle existe et si elle est à 'true'.
    if (typeof customerIsPro !== 'undefined' && customerIsPro === true) {

        // Sélecteur pour le lien du nom du client dans le header du thème classic
        const customerNameLink = document.querySelector('#_desktop_user_info .account > .hidden-sm-down');

        if (customerNameLink) {
            // On vérifie qu'un badge n'existe pas déjà pour éviter les doublons
            if (!customerNameLink.querySelector('.pro-badge')) {
                const proBadge = document.createElement('span');
                proBadge.innerText = 'PRO';
                proBadge.classList.add('pro-badge'); // On utilise une classe pour le style

                customerNameLink.appendChild(proBadge);
            }
        }
    }
});