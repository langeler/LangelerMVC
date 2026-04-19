<?php $order = is_array($order ?? null) ? $order : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'OrderModule',
        'headline' => $headline ?? ($order['order_number'] ?? 'Order'),
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <div class="section">
        <h2>Order summary</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Order number' => $order['order_number'] ?? '',
                'Status' => $order['status'] ?? '',
                'Payment status' => $order['payment_status'] ?? '',
                'Payment method' => $order['payment_method'] ?? '',
                'Payment flow' => $order['payment_flow'] ?? '',
                'Payment reference' => $order['payment_reference'] ?? '',
                'Provider reference' => $order['payment_provider_reference'] ?? '',
                'Total' => $order['total'] ?? '',
            ],
        ]) ?>
    </div>

    <?php if (!empty($order['payment_next_action']) && is_array($order['payment_next_action'])): ?>
        <div class="section">
            <h2>Payment next action</h2>
            <?= $view->renderComponent('DefinitionGrid', [
                'items' => array_map(
                    static fn(mixed $value): string => is_scalar($value) || $value === null
                        ? (string) $value
                        : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $order['payment_next_action']
                ),
            ]) ?>
        </div>
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
            'rows' => is_array($order['items'] ?? null) ? $order['items'] : [],
            'empty' => 'No order items are available.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Addresses</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'type' => 'Type',
                'name' => 'Name',
                'line_one' => 'Address',
                'city' => 'City',
                'country' => 'Country',
                'email' => 'Email',
            ],
            'rows' => is_array($order['addresses'] ?? null) ? $order['addresses'] : [],
            'empty' => 'No addresses were stored for this order.',
        ]) ?>
    </div>
</section>
