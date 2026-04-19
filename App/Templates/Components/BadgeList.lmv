<?php

$items = is_array($items ?? null) ? $items : [];
$empty = (string) ($empty ?? '');

if ($items === []) {
    if ($empty !== ''): ?>
        <p><?= $view->escape($empty) ?></p>
    <?php endif;

    return;
}
?>
<ul class="badge-list">
    <?php foreach ($items as $item): ?>
        <li><?= $view->escape($item) ?></li>
    <?php endforeach; ?>
</ul>
