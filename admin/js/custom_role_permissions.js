$(document).ready(function () {
    $('#rolid').select2({
        width: '100%'
    });

       var toastEl = document.getElementById('rolePermissionsToast');

    if (toastEl) {

        toastEl.style.display = 'block';

        setTimeout(() => {

            toastEl.style.display = 'none';

        }, 4000);

    }

    
});

