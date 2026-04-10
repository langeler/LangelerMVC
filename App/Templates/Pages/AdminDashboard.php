<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Admin dashboard'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <h2>Metrics</h2>
    <ul>
        <?php foreach (($metrics ?? []) as $label => $value): ?>
            <li><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
    <p><a href="/admin/users">Users</a> · <a href="/admin/roles">Roles</a> · <a href="/admin/system">System</a></p>
</section>
