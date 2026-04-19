<?php

$items = is_array($items ?? null) ? $items : [];
$items = array_filter(
    $items,
    static fn(mixed $value): bool => $value !== null && $value !== ''
);

if ($items === []) {
    return;
}
?>
<div class="meta">
    <?php foreach ($items as $label => $value): ?>
        <span><?= $view->escape((string) $label) ?>: <?= $view->escape($value) ?></span>
    <?php endforeach; ?>
</div>
