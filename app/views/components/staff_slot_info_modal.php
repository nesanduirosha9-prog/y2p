<!-- views/components/staff_slot_info_modal.php -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap');

    #staffSlotInfoModal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.45);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
        font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }
    #staffSlotInfoModal.open {
        opacity: 1;
        pointer-events: auto;
    }

    #staffSlotInfoModal .modal-box {
        background: transparent;
        border-radius: 14px;
        padding: 0;
        width: 300px;
        max-width: calc(100vw - 48px);
        height: 440px;
        max-height: calc(100vh - 48px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        position: relative;
        transform: scale(0.9);
        transition: transform 0.2s ease;
    }
    #staffSlotInfoModal.open .modal-box {
        transform: scale(1);
    }

    #staffSlotInfoModal .modal-close {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.35);
        border: none;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        font-size: 1.2rem;
        line-height: 1;
        cursor: pointer;
        color: #fff;
        z-index: 40;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s ease;
    }
    #staffSlotInfoModal .modal-close:hover {
        background: rgba(0, 0, 0, 0.55);
    }

    #staffSlotInfoModal .image-card {
        position: relative;
        width: 100%;
        height: 100%;
        border-radius: 14px;
        overflow: hidden;
        background: #000;
    }
    #staffSlotInfoModal .image-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        user-select: none;
    }

    #staffSlotInfoModal .arrow-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.3);
        border: none;
        color: #fff;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        font-size: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s ease;
        z-index: 20;
    }
    #staffSlotInfoModal .arrow-btn:hover {
        background: rgba(0, 0, 0, 0.5);
    }
    #staffSlotInfoModal .arrow-left { left: 12px; }
    #staffSlotInfoModal .arrow-right { right: 12px; }

    #staffSlotInfoModal .card-overlay-bottom {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 40px 14px 16px;
        background: linear-gradient(to top, rgba(0,0,0,0.65), rgba(0,0,0,0));
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        z-index: 30;
    }
    #staffSlotInfoModal .person-name {
        color: #fff;
        font-size: 0.9rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 95%;
    }

    /* Shared "icon trigger" button style, used by both Replace and Staff Search */
    #staffSlotInfoModal .icon-trigger-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1.5px solid rgba(255,255,255,0.85);
        background: rgba(0, 0, 0, 0.3);
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
    }
    #staffSlotInfoModal .icon-trigger-btn:hover {
        background: rgba(0, 0, 0, 0.55);
        border-color: #fff;
        transform: scale(1.06);
    }
    #staffSlotInfoModal .icon-trigger-btn svg {
        width: 18px;
        height: 18px;
    }

    /* ---------- Shared "frosted glass" dropdown (Replace AND Staff Search) ---------- */
    #staffSlotInfoModal .transparent-dropdown {
        display: none;
        position: absolute;
        background: rgba(24, 24, 28, 0.72);
        backdrop-filter: blur(14px) saturate(140%);
        -webkit-backdrop-filter: blur(14px) saturate(140%);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 12px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45), 0 2px 8px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        z-index: 50;
        width: 236px;
        opacity: 0;
        transform: translateY(4px) scale(0.97);
        transition: opacity 0.15s ease, transform 0.15s ease;
    }
    #staffSlotInfoModal .transparent-dropdown.open {
        display: block;
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    #staffSlotInfoModal .transparent-dropdown ul {
        list-style: none;
        margin: 0;
        padding: 6px 0;
        max-height: 190px;
        overflow-y: auto;
        overflow-x: hidden;
        text-align: left;

        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    #staffSlotInfoModal .transparent-dropdown ul::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
        background: transparent;
    }
    #staffSlotInfoModal .transparent-dropdown li {
        padding: 9px 12px;
        font-size: 0.8rem;
        font-weight: 500;
        letter-spacing: 0.01em;
        cursor: pointer;
        color: rgba(255, 255, 255, 0.92);
        display: flex;
        align-items: center;
        gap: 10px;
        word-break: break-all;
        transition: background 0.12s ease;
    }
    #staffSlotInfoModal .transparent-dropdown li:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    #staffSlotInfoModal .transparent-dropdown li:active {
        background: rgba(255, 255, 255, 0.16);
    }
    #staffSlotInfoModal .transparent-dropdown li.no-match {
        color: rgba(255,255,255,0.5);
        font-style: italic;
        cursor: default;
        justify-content: center;
    }
    #staffSlotInfoModal .transparent-dropdown li.no-match:hover {
        background: none;
    }

    /* Round white tick badge, black check */
    #staffSlotInfoModal .transparent-dropdown li .tick {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        border-radius: 50%;
        background: #fff;
        color: #000;
        font-weight: 700;
        font-size: 0.65rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    /* Empty placeholder circle for unticked rows, keeps alignment consistent */
    #staffSlotInfoModal .transparent-dropdown li .tick:empty {
        background: transparent;
        border: 1.5px solid rgba(255, 255, 255, 0.35);
    }

    /* Shared inner search input style (used inside both dropdowns) */
    #staffSlotInfoModal .dropdown-search-input {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 12px;
        background: rgba(255, 255, 255, 0.05);
        border: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        color: #fff;
        font-family: inherit;
        font-size: 0.8rem;
        letter-spacing: 0.01em;
        outline: none;
    }
    #staffSlotInfoModal .dropdown-search-input::placeholder {
        color: rgba(255,255,255,0.45);
    }
    #staffSlotInfoModal .dropdown-search-input:focus {
        background: rgba(255, 255, 255, 0.08);
    }

    /* Replace: icon bottom-center, dropdown opens upward above it */
    #staffSlotInfoModal .replace-dropdown {
        bottom: 84px;
        left: 50%;
        transform-origin: bottom center;
        margin-left: -118px;
    }
    #staffSlotInfoModal .replace-dropdown.open {
        transform: translateY(0) scale(1);
    }

    /* Staff search: icon top-left, dropdown opens downward below it */
    #staffSlotInfoModal .search-bar-wrap {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 40;
    }
    #staffSlotInfoModal .staff-dropdown {
        top: calc(100% + 8px);
        left: 0;
        transform-origin: top left;
    }
</style>

<div id="staffSlotInfoModal">
    <div class="modal-box">
        <div class="image-card">
            <button type="button" class="modal-close" id="closeStaffSlotInfoModal">&times;</button>

            <!-- Staff search: icon button + dropdown (input + ticked list), same pattern as Replace -->
            <div class="search-bar-wrap">
                <button type="button" class="icon-trigger-btn" id="staffSearchBtn" title="Find staff member">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
                <div class="transparent-dropdown staff-dropdown" id="staffSearchDropdown">
                    <input type="text" id="staffSearchInput" class="dropdown-search-input" placeholder="Search by name or email" autocomplete="off">
                    <ul id="staffTickList"></ul>
                </div>
            </div>

            <button type="button" class="arrow-btn arrow-left" id="prevImageBtn">&#10094;</button>
            <img id="cardImage" src="/images/person1.jpeg" alt="Staff member photo">
            <button type="button" class="arrow-btn arrow-right" id="nextImageBtn">&#10095;</button>

            <div class="card-overlay-bottom">
                <div class="person-name" id="personName">tmd@ucsc.cmb.ac.lk</div>

                <button type="button" class="icon-trigger-btn" id="replaceBtn" title="Replace this staff member">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="17 1 21 5 17 9"></polyline>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                        <polyline points="7 23 3 19 7 15"></polyline>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                    </svg>
                </button>

                <div class="transparent-dropdown replace-dropdown" id="replaceDropdown">
                    <input type="text" id="replaceSearchInput" class="dropdown-search-input" placeholder="Search by name or email" autocomplete="off">
                    <ul id="replaceNameList"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // ---------- Data ----------
    // Fixed list of 5 names. `order[0..3]` = ticked/selected staff,
    // `order[4]` = the unticked "5th" candidate.
    var NAME_POOL = [
        "tmd@ucsc.cmb.ac.lk",
        "abc@ucsc.cmb.ac.lk",
        "bcd@ucsc.cmb.ac.lk",
        "cde@ucsc.cmb.ac.lk",
        "def@ucsc.cmb.ac.lk"
    ];

    // `order` tracks selection state only. NAME_POOL itself is never
    // reordered, so the dropdown list always renders in a STABLE order —
    // only the tick mark next to each name changes.
    var order = NAME_POOL.slice();

    var CARD_IMAGES = [
        { src: "/images/person1.jpeg", name: order[0] },
        { src: "/images/person2.jpeg", name: order[1] },
        { src: "/images/person3.jpeg", name: order[2] },
        { src: "/images/person4.jpeg", name: order[3] }
    ];
    var REPLACE_IMAGE_SRC = "/images/person5.jpeg";

    var currentIndex = 0;

    var modal = document.getElementById('staffSlotInfoModal');
    var closeBtn = document.getElementById('closeStaffSlotInfoModal');
    var cardImage = document.getElementById('cardImage');
    var personName = document.getElementById('personName');
    var prevBtn = document.getElementById('prevImageBtn');
    var nextBtn = document.getElementById('nextImageBtn');

    var replaceBtn = document.getElementById('replaceBtn');
    var replaceDropdown = document.getElementById('replaceDropdown');
    var replaceSearchInput = document.getElementById('replaceSearchInput');
    var replaceNameList = document.getElementById('replaceNameList');

    var staffSearchBtn = document.getElementById('staffSearchBtn');
    var staffSearchDropdown = document.getElementById('staffSearchDropdown');
    var staffSearchInput = document.getElementById('staffSearchInput');
    var staffTickList = document.getElementById('staffTickList');

    // ---------- Image card ----------
    function renderCard() {
        var item = CARD_IMAGES[currentIndex];
        cardImage.src = item.src;
        personName.textContent = item.name;
    }

    prevBtn.addEventListener('click', function () {
        currentIndex = (currentIndex - 1 + CARD_IMAGES.length) % CARD_IMAGES.length;
        renderCard();
    });

    nextBtn.addEventListener('click', function () {
        currentIndex = (currentIndex + 1) % CARD_IMAGES.length;
        renderCard();
    });

    // ---------- Replace icon: searchable list (same 5 names) ----------
    function renderReplaceList(filter) {
        filter = (filter || '').trim().toLowerCase();
        replaceNameList.innerHTML = '';

        var matches = NAME_POOL.filter(function (n) {
            return n.toLowerCase().indexOf(filter) !== -1;
        });

        if (matches.length === 0) {
            var li = document.createElement('li');
            li.className = 'no-match';
            li.textContent = 'No staff found';
            replaceNameList.appendChild(li);
            return;
        }

        matches.forEach(function (name) {
            var li = document.createElement('li');
            li.textContent = name;
            li.addEventListener('click', function (e) {
                e.stopPropagation();
                CARD_IMAGES[currentIndex] = { src: REPLACE_IMAGE_SRC, name: name };
                renderCard();
                closeReplaceDropdown();
            });
            replaceNameList.appendChild(li);
        });
    }

    function openReplaceDropdown() {
        replaceDropdown.classList.add('open');
        replaceSearchInput.value = '';
        renderReplaceList('');
        replaceSearchInput.focus();
    }

    function closeReplaceDropdown() {
        replaceDropdown.classList.remove('open');
    }

    replaceBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (replaceDropdown.classList.contains('open')) {
            closeReplaceDropdown();
        } else {
            closeStaffSearchDropdown();
            openReplaceDropdown();
        }
    });

    replaceSearchInput.addEventListener('input', function () {
        renderReplaceList(replaceSearchInput.value);
    });

    replaceSearchInput.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    // ---------- Staff search icon: dropdown with input + ticked list ----------
    // Renders from the fixed NAME_POOL order so rows never jump around;
    // only the tick (derived from `order`) changes after a swap.
    function renderTickList(filter) {
        filter = (filter || '').trim().toLowerCase();
        staffTickList.innerHTML = '';

        var matches = NAME_POOL.filter(function (n) {
            return n.toLowerCase().indexOf(filter) !== -1;
        });

        if (matches.length === 0) {
            var emptyLi = document.createElement('li');
            emptyLi.className = 'no-match';
            emptyLi.textContent = 'No staff found';
            staffTickList.appendChild(emptyLi);
            return;
        }

        matches.forEach(function (name) {
            var posInOrder = order.indexOf(name);
            var isTicked = posInOrder < 4;

            var li = document.createElement('li');

            var tick = document.createElement('span');
            tick.className = 'tick';
            tick.textContent = isTicked ? '\u2713' : '';

            var label = document.createElement('span');
            label.textContent = name;

            li.appendChild(tick);
            li.appendChild(label);

            li.addEventListener('click', function (e) {
                e.stopPropagation();
                if (isTicked) return; // already selected, nothing to do

                // Swap this (unticked, position 4) name into position 3 ("the 4th"),
                // and move whatever was in position 3 out to position 4 (now unticked).
                var clickedIdx = order.indexOf(name);
                var tmp = order[3];
                order[3] = order[clickedIdx];
                order[clickedIdx] = tmp;

                // The 4th card's displayed name updates to the newly-ticked name.
                CARD_IMAGES[3].name = order[3];
                if (currentIndex === 3) {
                    personName.textContent = order[3];
                }

                renderTickList(staffSearchInput.value);
            });

            staffTickList.appendChild(li);
        });
    }

    function openStaffSearchDropdown() {
        staffSearchDropdown.classList.add('open');
        staffSearchInput.value = '';
        renderTickList('');
        staffSearchInput.focus();
    }

    function closeStaffSearchDropdown() {
        staffSearchDropdown.classList.remove('open');
    }

    staffSearchBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (staffSearchDropdown.classList.contains('open')) {
            closeStaffSearchDropdown();
        } else {
            closeReplaceDropdown();
            openStaffSearchDropdown();
        }
    });

    staffSearchInput.addEventListener('input', function () {
        renderTickList(staffSearchInput.value);
    });

    staffSearchInput.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    // ---------- Close dropdowns when clicking outside ----------
    document.addEventListener('click', function (e) {
        if (!replaceDropdown.contains(e.target) && e.target !== replaceBtn && !replaceBtn.contains(e.target)) {
            closeReplaceDropdown();
        }
        if (!staffSearchDropdown.contains(e.target) && e.target !== staffSearchBtn && !staffSearchBtn.contains(e.target)) {
            closeStaffSearchDropdown();
        }
    });

    // ---------- Modal open/close ----------
    closeBtn.addEventListener('click', function () {
        modal.classList.remove('open');
    });

    // Initial render
    renderCard();

    // Expose an opener so the rest of your app can show this modal.
    window.openStaffSlotInfoModal = function () {
        modal.classList.add('open');
    };
})();
</script>