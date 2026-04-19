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
                'Available drivers' => implode(', ', (array) ($payment['available_drivers'] ?? [])),
                'Supported methods' => implode(', ', (array) ($payment['supported_methods'] ?? [])),
                'Supported flows' => implode(', ', (array) ($payment['supported_flows'] ?? [])),
            ],
        ]) ?>
    </div>

    <div class="section">
        <h2>Provider catalog</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'driver' => 'Driver',
                'label' => 'Label',
                'regions' => 'Regions',
                'methods' => 'Methods',
                'flows' => 'Flows',
                'mode' => 'Mode',
            ],
            'rows' => array_map(static function (array $provider): array {
                return [
                    'driver' => $provider['driver'] ?? '',
                    'label' => $provider['label'] ?? '',
                    'regions' => implode(', ', (array) ($provider['regions'] ?? [])),
                    'methods' => implode(', ', (array) ($provider['methods'] ?? [])),
                    'flows' => implode(', ', (array) ($provider['flows'] ?? [])),
                    'mode' => $provider['mode'] ?? '',
                ];
            }, is_array($payment['catalog'] ?? null) ? array_values($payment['catalog']) : []),
            'empty' => 'No payment providers are currently enabled.',
        ]) ?>
    </div>
</section>
