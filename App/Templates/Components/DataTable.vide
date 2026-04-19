<?php

$columns = is_array($columns ?? null) ? $columns : [];
$rows = is_array($rows ?? null) ? $rows : [];
$empty = (string) ($empty ?? '');

if ($rows === []) {
    if ($empty !== ''): ?>
        <p><?= $view->escape($empty) ?></p>
    <?php endif;

    return;
}
?>
<table class="data-table">
    <thead>
        <tr>
            <?php foreach ($columns as $key => $label): ?>
                <th><?= $view->escape(is_string($key) ? $label : $label) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($columns as $key => $label): ?>
                    <?php
                    $columnKey = is_string($key) ? $key : (string) $label;
                    $value = is_array($row) ? ($row[$columnKey] ?? '') : '';

                    if (is_array($value)) {
                        $value = implode(', ', array_map(static fn(mixed $item): string => (string) $item, $value));
                    }
                    ?>
                    <td><?= $view->escape($value) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
