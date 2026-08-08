document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('greet-btn');
    if (btn) {
        btn.addEventListener('click', () => {
            alert('Hello from main.js!');
        });
    }
});