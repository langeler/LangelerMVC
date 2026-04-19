<?php $cart = is_array($cart ?? null) ? $cart : []; ?>
<?php $payment = is_array($payment ?? null) ? $payment : []; ?>
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
                'Payment driver' => $payment['driver'] ?? '',
                'Default method' => $payment['default_method'] ?? '',
                'Default flow' => $payment['default_flow'] ?? '',
            ],
        ]) ?>
    </div>

    <div class="section">
        <h2>Payment compatibility</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Supported methods' => implode(', ', (array) ($payment['supported_methods'] ?? [])),
                'Supported flows' => implode(', ', (array) ($payment['supported_flows'] ?? [])),
            ],
        ]) ?>
    </div>
</section>
