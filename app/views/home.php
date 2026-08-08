<h1>User List</h1>
<ul>
<?php foreach ($users as $user): ?>
    <li><?= htmlspecialchars($user['name']) ?></li>
<?php endforeach; ?>
</ul>