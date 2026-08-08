<h1>Welcome</h1>
<p>This is the home page. Below is a list of users:</p>

<ul class="user-list">
    <?php foreach ($users ?? [] as $user): ?>
        <li><?= htmlspecialchars($user['name']) ?></li>
    <?php endforeach; ?>
</ul>

<button id="greet-btn" class="btn">Say Hello</button>