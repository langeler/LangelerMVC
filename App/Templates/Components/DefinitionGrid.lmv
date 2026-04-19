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
<dl class="definition-grid">
    <?php foreach ($items as $label => $value): ?>
        <?php
        if (is_array($value)) {
            $value = implode(', ', array_map(static fn(mixed $item): string => (string) $item, $value));
        }
        ?>
        <dt><?= $view->escape((string) $label) ?></dt>
        <dd><?= $view->escape($value) ?></dd>
    <?php endforeach; ?>
</dl>
