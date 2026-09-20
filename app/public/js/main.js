// main.js — leftover framework-boilerplate script for views/layouts/main.php
// (the generic "MyApp" starter layout, not used by any real StaffSync page).
// 1. Wires the #greet-btn demo button, if present, to a hello alert.
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('greet-btn');
    if (btn) {
        btn.addEventListener('click', () => {
            alert('Hello from main.js!');
        });
    }
});