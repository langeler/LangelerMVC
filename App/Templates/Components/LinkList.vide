<?php

$links = is_array($links ?? null) ? $links : [];

if ($links === []) {
    return;
}
?>
<nav class="link-list" aria-label="<?= $view->escape((string) ($label ?? 'Related links')) ?>">
    <?php foreach ($links as $link): ?>
        <?php if (is_array($link) && ($link['href'] ?? '') !== '' && ($link['label'] ?? '') !== ''): ?>
            <a href="<?= $view->escape((string) $link['href']) ?>"><?= $view->escape((string) $link['label']) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
