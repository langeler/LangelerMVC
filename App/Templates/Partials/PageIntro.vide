<?php

$eyebrow = (string) ($eyebrow ?? $tag ?? '');
$headlineText = (string) ($headline ?? '');
$summaryText = (string) ($summary ?? '');
$containerClass = (string) ($containerClass ?? 'intro');
$eyebrowClass = (string) ($eyebrowClass ?? 'intro__eyebrow');
$headlineClass = (string) ($headlineClass ?? 'intro__headline');
$summaryClass = (string) ($summaryClass ?? 'intro__summary');
?>
<header class="<?= $view->escape($containerClass) ?>">
    <?php if ($eyebrow !== ''): ?>
        <p class="<?= $view->escape($eyebrowClass) ?>"><?= $view->escape($eyebrow) ?></p>
    <?php endif; ?>
    <?php if ($headlineText !== ''): ?>
        <h1 class="<?= $view->escape($headlineClass) ?>"><?= $view->escape($headlineText) ?></h1>
    <?php endif; ?>
    <?php if ($summaryText !== ''): ?>
        <p class="<?= $view->escape($summaryClass) ?>"><?= $view->escape($summaryText) ?></p>
    <?php endif; ?>
</header>
