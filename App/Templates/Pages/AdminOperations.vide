<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Operations',
        'summary' => $summary ?? '',
    ]) ?>

    <?php foreach (($operations ?? []) as $section => $payload): ?>
        <article class="section">
            <h2><?= $view->escape(ucfirst((string) $section)) ?></h2>
            <pre class="system-dump"><?= $view->escape($view->toJson($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
        </article>
    <?php endforeach; ?>
</section>
