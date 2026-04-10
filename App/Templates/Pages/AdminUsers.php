<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Users'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Roles</th><th>Permissions</th></tr>
        </thead>
        <tbody>
            <?php foreach (($users ?? []) as $user): ?>
                <tr>
                    <td><?= htmlspecialchars((string) ($user['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(implode(', ', $user['roles'] ?? []), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(implode(', ', $user['permissions'] ?? []), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
