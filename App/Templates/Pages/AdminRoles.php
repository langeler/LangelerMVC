<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Roles'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <?php foreach (($roles ?? []) as $role): ?>
        <article>
            <h2><?= htmlspecialchars((string) ($role['label'] ?? $role['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars((string) ($role['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p>Permissions: <?= htmlspecialchars(implode(', ', $role['permissions'] ?? []), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
    <?php endforeach; ?>
</section>
