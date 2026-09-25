// password_toggle.js — a show/hide button on every password field of the
// auth pages (login, sign-up, forgot password). Loaded by layouts/auth.php.
//
// Each field is wrapped in .pw-field and given the .pw-input class, so the
// page CSS keeps styling it while its type flips between password and text.
(function () {
    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        const wrap = document.createElement('div');
        wrap.className = 'pw-field';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
        input.classList.add('pw-input');

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pw-toggle';
        btn.setAttribute('aria-label', 'Show password');
        btn.innerHTML = '<i class="fa-regular fa-eye"></i>';
        wrap.appendChild(btn);

        btn.addEventListener('click', function () {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.innerHTML = show ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
            input.focus();
        });
    });
})();
