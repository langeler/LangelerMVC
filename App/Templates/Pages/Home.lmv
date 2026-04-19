<section class="<?= $view->escape((string) ($pageClass ?? 'page-home')) ?>">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'WebModule',
        'headline' => $headline ?? '',
        'summary' => $summary ?? '',
    ]) ?>

    <p class="body-copy"><?= $view->escape((string) ($body ?? '')) ?></p>

    <div class="cta-row">
        <a href="<?= $view->escape((string) ($callToAction['href'] ?? '#')) ?>">
            <?= $view->escape((string) ($callToAction['label'] ?? 'Continue')) ?>
        </a>
        <?= $view->renderComponent('BadgeList', [
            'items' => ['Extendable', 'Maintainable', 'Readable'],
        ]) ?>
    </div>
</section>
