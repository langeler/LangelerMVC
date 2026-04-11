<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Roles',
        'summary' => $summary ?? '',
    ]) ?>

    <?php foreach (($roles ?? []) as $role): ?>
        <article class="section">
            <h2><?= $view->escape((string) ($role['label'] ?? $role['name'] ?? 'Role')) ?></h2>
            <p><?= $view->escape((string) ($role['description'] ?? '')) ?></p>
            <?= $view->renderComponent('BadgeList', [
                'items' => is_array($role['permissions'] ?? null) ? $role['permissions'] : [],
                'empty' => 'No permissions assigned.',
            ]) ?>
        </article>
    <?php endforeach; ?>
</section>
