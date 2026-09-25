// searchable_select.js — the searchable dropdown from the Workload staff
// picker (.sched-combo: a trigger that opens a popover with a search box and
// a filtered option list), packaged so any native <select> can use it.
//
// Usage: mark a <select> with data-searchable (optionally
// data-search-placeholder="…") and call SearchableSelect.enhance(root).
//
// The <select> stays in the DOM, hidden, as the source of truth: picking an
// option sets its value and fires 'change', so code that reads .value or
// listens for 'change' keeps working untouched. After setting .value from
// code, call SearchableSelect.refresh(select) to update the visible label.
//
// The trigger takes over the select's own classes (and its wrapper the inline
// style), so it keeps the look of wherever it sits — toolbar, side panel or
// modal. The popover is position:fixed, so overflow on a panel or modal
// can't clip it.
(function () {
    'use strict';

    let openSS = null;

    function make(tag, cls) {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        return n;
    }

    function enhance(root) {
        (root || document).querySelectorAll('select[data-searchable]').forEach(setup);
    }

    function setup(select) {
        if (select._ss) return;

        const wrap = make('div', 'ss');
        const style = select.getAttribute('style');
        if (style) wrap.setAttribute('style', style);

        const trigger = make('button', 'ss-trigger ' + select.className);
        trigger.type = 'button';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        const label = make('span', 'ss-label');
        trigger.append(label, make('i', 'fa-solid fa-chevron-down ss-caret'));

        const pop = make('div', 'ss-pop');
        pop.hidden = true;
        const searchWrap = make('div', 'ss-search');
        searchWrap.appendChild(make('i', 'fa-solid fa-magnifying-glass'));
        const search = make('input', 'ss-search-input');
        search.type = 'text';
        search.autocomplete = 'off';
        search.placeholder = select.dataset.searchPlaceholder || 'Search…';
        searchWrap.appendChild(search);
        const list = make('div', 'ss-list');
        list.setAttribute('role', 'listbox');
        pop.append(searchWrap, list);

        select.parentNode.insertBefore(wrap, select);
        wrap.append(select, trigger, pop);
        select.classList.add('ss-native');
        select.tabIndex = -1;

        const ss = { select, wrap, trigger, label, pop, search, list, active: -1 };
        select._ss = ss;

        trigger.addEventListener('click', () => (ss.pop.hidden ? open(ss) : close(ss)));
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' && ss.pop.hidden) { e.preventDefault(); open(ss); }
        });
        search.addEventListener('input', () => { ss.active = -1; render(ss); });
        search.addEventListener('keydown', (e) => onSearchKey(ss, e));
        list.addEventListener('mousedown', (e) => e.preventDefault()); // keep focus in the search box
        list.addEventListener('click', (e) => {
            const opt = e.target.closest('[data-index]');
            if (opt) choose(ss, parseInt(opt.dataset.index, 10));
        });
        select.addEventListener('change', () => sync(ss));

        sync(ss);
    }

    function sync(ss) {
        const opt = ss.select.options[ss.select.selectedIndex];
        ss.label.textContent = opt ? opt.textContent.trim() : '';
        ss.trigger.classList.toggle('is-placeholder', !opt || opt.value === '');
        ss.trigger.disabled = ss.select.disabled;
    }

    /** Option rows matching the search box. The empty-value option ("All …",
     *  "Select …") only shows with no query, as in the Workload picker. */
    function matches(ss) {
        const q = ss.search.value.trim().toLowerCase();
        const out = [];
        Array.from(ss.select.options).forEach((o, i) => {
            if (o.disabled && o.value === '') return;
            if (q && o.value === '') return;
            const text = (o.textContent + ' ' + o.value).toLowerCase();
            if (!q || text.includes(q)) out.push({ o, i });
        });
        return out;
    }

    function render(ss) {
        const rows = matches(ss);
        if (!rows.length) {
            ss.list.innerHTML = '<p class="ss-empty">No matches.</p>';
            return;
        }
        ss.list.innerHTML = '';
        rows.forEach(({ o, i }, n) => {
            const b = make('button', 'ss-opt');
            b.type = 'button';
            b.setAttribute('role', 'option');
            b.dataset.index = i;
            b.textContent = o.textContent.trim();
            if (i === ss.select.selectedIndex) b.classList.add('is-selected');
            if (n === ss.active) b.classList.add('is-active');
            ss.list.appendChild(b);
        });
    }

    function place(ss) {
        const r = ss.trigger.getBoundingClientRect();
        const width = Math.max(r.width, 240);
        const left = Math.max(8, Math.min(r.left, window.innerWidth - width - 8));
        ss.pop.style.width = width + 'px';
        ss.pop.style.left = left + 'px';

        const h = ss.pop.offsetHeight;
        const below = window.innerHeight - r.bottom;
        const top = (below < h + 12 && r.top > below) ? r.top - h - 6 : r.bottom + 6;
        ss.pop.style.top = Math.max(8, top) + 'px';
    }

    function open(ss) {
        if (openSS && openSS !== ss) close(openSS);
        openSS = ss;
        ss.search.value = '';
        ss.active = -1;
        render(ss);
        ss.pop.hidden = false;
        ss.wrap.classList.add('is-open');
        ss.trigger.setAttribute('aria-expanded', 'true');
        place(ss);
        ss.search.focus();
        const sel = ss.list.querySelector('.is-selected');
        if (sel) sel.scrollIntoView({ block: 'nearest' });
    }

    function close(ss) {
        ss.pop.hidden = true;
        ss.wrap.classList.remove('is-open');
        ss.trigger.setAttribute('aria-expanded', 'false');
        if (openSS === ss) openSS = null;
    }

    function choose(ss, index) {
        const changed = ss.select.selectedIndex !== index;
        ss.select.selectedIndex = index;
        sync(ss);
        close(ss);
        ss.trigger.focus();
        if (changed) ss.select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function onSearchKey(ss, e) {
        const opts = ss.list.querySelectorAll('[data-index]');
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!opts.length) return;
            const step = e.key === 'ArrowDown' ? 1 : -1;
            ss.active = (ss.active + step + opts.length) % opts.length;
            opts.forEach((b, n) => b.classList.toggle('is-active', n === ss.active));
            opts[ss.active].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const pick = opts[ss.active] || opts[0];
            if (pick) choose(ss, parseInt(pick.dataset.index, 10));
        } else if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation(); // don't also close the panel/modal behind it
            close(ss);
            ss.trigger.focus();
        } else if (e.key === 'Tab') {
            close(ss);
        }
    }

    document.addEventListener('click', (e) => {
        if (openSS && !openSS.wrap.contains(e.target)) close(openSS);
    });
    // Follow the trigger when a panel or the page scrolls under it.
    document.addEventListener('scroll', (e) => {
        if (openSS && !openSS.pop.contains(e.target)) place(openSS);
    }, true);
    window.addEventListener('resize', () => { if (openSS) place(openSS); });

    window.SearchableSelect = {
        enhance,
        refresh(select) { if (select && select._ss) sync(select._ss); },
    };
})();
