<?php $cart = is_array($cart ?? null) ? $cart : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'OrderModule',
        'headline' => $headline ?? 'Checkout',
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <div class="section">
        <h2>Checkout cart</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'name' => 'Product',
                'quantity' => 'Qty',
                'unit_price' => 'Unit price',
                'line_total' => 'Line total',
            ],
            'rows' => is_array($cart['items'] ?? null) ? $cart['items'] : [],
            'empty' => 'There are no items available for checkout.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Summary</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Currency' => $cart['currency'] ?? '',
                'Items' => $cart['item_count'] ?? 0,
                'Subtotal' => $cart['subtotal'] ?? '',
            ],
        ]) ?>
    </div>
</section>
