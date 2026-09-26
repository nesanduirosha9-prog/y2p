<?php
// components/auth_stepper.php — 3-step progress tracker for the auth wizards
// (signup, forgot password). Each page renders it twice: dark inside the
// brand panel (desktop) and light above the form (tablet/phone, where the
// brand panel is hidden). signup.js / forgot_password.js update every
// .progress-tracker on the page together.
// Expects: $stepLabels (array of step names), optional $stepperClass.
?>
<div class="progress-tracker <?= $stepperClass ?? '' ?>">
    <?php foreach ($stepLabels as $i => $label): ?>
        <div class="step<?= $i === 0 ? ' active' : '' ?>">
            <div class="step-circle">
                <span class="step-num"><?= $i + 1 ?></span>
                <i class="fa-solid fa-check step-check"></i>
            </div>
            <span class="step-text"><?= htmlspecialchars($label) ?></span>
        </div>
    <?php endforeach; ?>
</div>
