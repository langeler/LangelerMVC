<?php $order = is_array($order ?? null) ? $order : []; ?>
<?php $lookup = is_array($lookup ?? null) ? $lookup : []; ?>
<?php $actions = is_array($order['actions'] ?? null) ? $order['actions'] : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'OrderModule',
        'headline' => $headline ?? ($order['order_number'] ?? 'Order'),
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <?php if ($order === []): ?>
        <div class="section">
            <h2>Order lookup</h2>
            <p>We could not match the current return request to a stored order reference.</p>
            <p>
                <a href="<?= $view->escape((string) ($lookup['complete_url'] ?? '/orders/complete')) ?>">Completion surface</a> |
                <a href="<?= $view->escape((string) ($lookup['cancelled_url'] ?? '/orders/cancelled')) ?>">Cancellation surface</a> |
                <a href="/shop">Storefront catalog</a>
            </p>
        </div>
    <?php else: ?>
    <div class="section">
        <h2>Order summary</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Order number' => $order['order_number'] ?? '',
                'Status' => $order['status'] ?? '',
                'Fulfillment status' => $order['fulfillment_status'] ?? '',
                'Inventory status' => $order['inventory_status'] ?? '',
                'Payment status' => $order['payment_status'] ?? '',
                'Payment method' => $order['payment_method'] ?? '',
                'Payment flow' => $order['payment_flow'] ?? '',
                'Payment reference' => $order['payment_reference'] ?? '',
                'Provider reference' => $order['payment_provider_reference'] ?? '',
                'Subtotal' => $order['subtotal'] ?? '',
                'Discount' => $order['discount'] ?? '',
                'Shipping' => $order['shipping'] ?? '',
                'Tax' => $order['tax'] ?? '',
                'Total' => $order['total'] ?? '',
            ],
        ]) ?>
    </div>

    <?php if ($actions !== []): ?>
        <div class="section">
            <h2>Available actions</h2>

            <?php if (!empty($actions['view'])): ?>
                <p><a href="<?= $view->escape((string) $actions['view']) ?>">Open the full order view</a></p>
            <?php endif; ?>

            <?php if (!empty($actions['continue_payment'])): ?>
                <p><a href="<?= $view->escape((string) $actions['continue_payment']) ?>">Continue payment with the provider</a></p>
            <?php endif; ?>

            <?php foreach (['capture' => 'Capture payment', 'reconcile' => 'Reconcile payment', 'refund' => 'Refund payment', 'cancel' => 'Cancel order'] as $key => $label): ?>
                <?php if (!empty($actions[$key])): ?>
                    <form method="post" action="<?= $view->escape((string) $actions[$key]) ?>">
                        <button type="submit"><?= $view->escape($label) ?></button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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

    <?php if (!empty($order['addresses']) && is_array($order['addresses'])): ?>
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
                'rows' => $order['addresses'],
                'empty' => 'No addresses were stored for this order.',
            ]) ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</section>
