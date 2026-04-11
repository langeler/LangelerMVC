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
<ul class="code-list">
    <?php foreach ($items as $item): ?>
        <li><code><?= $view->escape($item) ?></code></li>
    <?php endforeach; ?>
</ul>
