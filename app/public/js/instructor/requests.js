// Instructor Requests JS
document.addEventListener('DOMContentLoaded', () => {
    const newRequestBtn = document.getElementById('newRequestBtn');
    const newRequestForm = document.getElementById('newRequestForm');
    const closeRequestForm = document.getElementById('closeRequestForm');
    const cancelRequestBtn = document.getElementById('cancelRequestBtn');

    if (newRequestBtn && newRequestForm) {
        newRequestBtn.addEventListener('click', () => {
            newRequestForm.style.display = 'block';
        });
    }

    const closeForm = () => {
        if (newRequestForm) newRequestForm.style.display = 'none';
    };

    if (closeRequestForm) closeRequestForm.addEventListener('click', closeForm);
    if (cancelRequestBtn) cancelRequestBtn.addEventListener('click', closeForm);
});
