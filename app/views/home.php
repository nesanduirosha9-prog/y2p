<!-- View: home.php - partial view listing users (used by examples).
     NOTE: unreferenced by any controller (HomeController renders
     'home/index' instead) — looks like a leftover from before that view
     moved into the home/ subfolder. -->
<h1>User List</h1>
<ul>
<?php foreach ($users as $user): ?>
    <li><?= htmlspecialchars($user['name']) ?></li>
<?php endforeach; ?>
</ul>