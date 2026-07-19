document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', function () {
            if (!loginForm.checkValidity()) {
                return;
            }

            submitBtn.disabled = true;
        });
    }
});
