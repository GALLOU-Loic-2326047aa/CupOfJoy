document.addEventListener('DOMContentLoaded', function() {

    if (typeof customerIsPro !== 'undefined' && customerIsPro === true) {

        const customerNameLink = document.querySelector('#_desktop_user_info .account > .hidden-sm-down');

        if (customerNameLink) {
            if (!customerNameLink.querySelector('.pro-badge')) {
                const proBadge = document.createElement('span');
                proBadge.innerText = 'PRO';
                proBadge.classList.add('pro-badge'); // On utilise une classe pour le style

                customerNameLink.appendChild(proBadge);
            }
        }
    }
});