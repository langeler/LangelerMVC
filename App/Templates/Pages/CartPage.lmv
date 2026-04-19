<?php $cart = is_array($cart ?? null) ? $cart : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'CartModule',
        'headline' => $headline ?? 'Cart',
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <div class="section">
        <h2>Items</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'name' => 'Product',
                'quantity' => 'Qty',
                'unit_price' => 'Unit price',
                'line_total' => 'Line total',
            ],
            'rows' => is_array($cart['items'] ?? null) ? $cart['items'] : [],
            'empty' => 'The cart is currently empty.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Cart summary</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Cart ID' => $cart['id'] ?? '',
                'Status' => $cart['status'] ?? '',
                'Currency' => $cart['currency'] ?? '',
                'Items' => $cart['item_count'] ?? 0,
                'Subtotal' => $cart['subtotal'] ?? '',
            ],
        ]) ?>
    </div>
</section>
