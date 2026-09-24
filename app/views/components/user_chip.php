<?php

use app\core\ViewHelpers;

// components/user_chip.php — the header profile chip: avatar, name/role, and a
// dropdown with Account Settings and Sign Out.
//
// Included inline (require, not render()) by layouts/dashboard.php, so it
// shares that scope, but it reads only one optional override from it:
//   $userEmail  the signed-in address
// The name and avatar come from ViewHelpers, so the chip renders correctly
// wherever it is included.
//
// It shows the member's own name, not their role title — the role is already
// evident from the sidebar they are looking at, and "Instructor" over
// "tmf@ucsc.cmb.ac.lk" told them nothing they did not know.
//
// The avatar is the member's 3-letter badge code (TMF, TMO, ...). It used to be
// a hardcoded 'IN' / 'TO' per role — identical for every member of that role —
// on a div with no click handler at all.
//
// A chosen photo replaces that code client-side: `data-avatar-for` marks every
// element js/dashboard.js should fill from the stored image. There is no
// avatar column on `staff` yet, so a photo lives in this browser only.
$avatarCode = ViewHelpers::currentAvatarCode();
$chipName = ViewHelpers::currentUserName();
$chipEmail = $userEmail ?? '';
?>

<div class="user-chip-wrap">
    <button type="button" class="user-chip" id="userChipBtn" aria-haspopup="menu" aria-expanded="false">
        <span class="user-avatar" data-avatar-for="<?= htmlspecialchars($avatarCode) ?>"><?= htmlspecialchars($avatarCode) ?></span>
        <span class="user-meta">
            <span class="user-name"><?= htmlspecialchars($chipName) ?></span>
            <span class="user-email"><?= htmlspecialchars($chipEmail) ?></span>
        </span>
        <i class="fa-solid fa-chevron-down"></i>
    </button>

    <div class="user-menu" id="userMenu" role="menu" hidden>
        <div class="user-menu-head">
            <span class="user-avatar user-avatar-lg" data-avatar-for="<?= htmlspecialchars($avatarCode) ?>"><?= htmlspecialchars($avatarCode) ?></span>
            <div class="user-menu-id">
                <p class="user-menu-name"><?= htmlspecialchars($chipName) ?></p>
                <p class="user-menu-email"><?= htmlspecialchars($chipEmail) ?></p>
                <p class="user-menu-code"><?= htmlspecialchars($avatarCode) ?></p>
            </div>
        </div>
        <a href="/settings" class="user-menu-item" role="menuitem">
            <i class="fa-solid fa-gear"></i> Account Settings
        </a>
        <!-- Every role can read its own activity record, so this lives in the
             profile menu rather than the sidebar: it is about the person, and
             only the Coordinator and the In-Charge have a department-wide
             Activity Log item in the navigation. -->
        <a href="/audit/me" class="user-menu-item" role="menuitem">
            <i class="fa-solid fa-clock-rotate-left"></i> View My Activity Log
        </a>
        <a href="/logout" class="user-menu-item user-menu-item-danger" role="menuitem">
            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>
    </div>
</div>
