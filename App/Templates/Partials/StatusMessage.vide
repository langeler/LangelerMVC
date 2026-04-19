<?php

$messageText = (string) ($message ?? '');
$tone = (string) ($tone ?? 'info');

if ($messageText === '') {
    return;
}
?>
<div class="message message--<?= $view->escape($tone) ?>">
    <strong><?= $view->escape($messageText) ?></strong>
</div>
