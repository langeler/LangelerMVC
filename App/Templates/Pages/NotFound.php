<section class="<?= $view->escape((string) ($pageClass ?? 'page-not-found')) ?>">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'Fallback',
        'headline' => $headline ?? '',
        'summary' => $summary ?? '',
    ]) ?>

    <p class="body-copy"><?= $view->escape((string) ($body ?? '')) ?></p>

    <div class="cta-row">
        <a href="<?= $view->escape((string) ($callToAction['href'] ?? '/')) ?>">
            <?= $view->escape((string) ($callToAction['label'] ?? 'Return Home')) ?>
        </a>
    </div>
</section>
