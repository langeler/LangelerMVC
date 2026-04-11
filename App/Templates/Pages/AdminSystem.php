<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'System',
        'summary' => $summary ?? '',
    ]) ?>

    <div class="section">
        <h2>Modules</h2>
        <?= $view->renderComponent('BadgeList', [
            'items' => is_array($modules ?? null) ? $modules : [],
            'empty' => 'No modules reported.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Runtime snapshot</h2>
        <pre class="system-dump"><?= $view->escape($view->toJson($system ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
    </div>
</section>
