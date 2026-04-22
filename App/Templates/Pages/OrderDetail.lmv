<?php

$order = is_array($order ?? null) ? $order : [];
$lookup = is_array($lookup ?? null) ? $lookup : [];
$actions = is_array($order['actions'] ?? null) ? $order['actions'] : [];
$trackingEvents = is_array($order['tracking_events'] ?? null) ? $order['tracking_events'] : [];
$trackingApps = is_array($order['tracking_apps'] ?? null) ? $order['tracking_apps'] : [];
$nextActionItems = !empty($order['payment_next_action']) && is_array($order['payment_next_action'])
    ? array_map(
        static fn(mixed $value): string => is_scalar($value) || $value === null
            ? (string) $value
            : (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        $order['payment_next_action']
    )
    : [];
?>
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
                'Shipping country' => $order['shipping_country'] ?? '',
                'Shipping zone' => $order['shipping_zone'] ?? '',
                'Shipping option' => $order['shipping_option_label'] ?? '',
                'Carrier' => $order['shipping_carrier_label'] ?? '',
                'Service' => $order['shipping_service_label'] ?? '',
                'Service point' => $order['shipping_service_point_name'] ?? '',
                'Shipment reference' => $order['shipment_reference'] ?? '',
                'Tracking number' => $order['tracking_number'] ?? '',
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

    <div class="section">
        <h2>Shipment and tracking</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Carrier portal' => $order['tracking_url'] ?? '',
                'Tracking number' => $order['tracking_number'] ?? '',
                'Shipped at' => $order['shipped_at'] ?? '',
                'Delivered at' => $order['delivered_at'] ?? '',
            ],
        ]) ?>

        <?php if (!empty($order['tracking_url'])): ?>
            <p><a href="<?= $view->escape((string) $order['tracking_url']) ?>">Open carrier tracking portal</a></p>
        <?php endif; ?>
    </div>

    <?php if ($trackingApps !== []): ?>
        <div class="section">
            <h2>Tracking apps</h2>
            <?= $view->renderComponent('DataTable', [
                'columns' => [
                    'label' => 'App',
                    'platforms' => 'Platforms',
                    'note' => 'Note',
                ],
                'rows' => array_map(static fn(array $app): array => [
                    'label' => (string) ($app['label'] ?? ''),
                    'platforms' => implode(', ', (array) ($app['platforms'] ?? [])),
                    'note' => (string) ($app['note'] ?? ''),
                ], $trackingApps),
                'empty' => 'No tracking apps are currently suggested for this order.',
            ]) ?>
        </div>
    <?php endif; ?>

    <?php if ($trackingEvents !== []): ?>
        <div class="section">
            <h2>Tracking timeline</h2>
            <?= $view->renderComponent('DataTable', [
                'columns' => [
                    'occurred_at' => 'When',
                    'status' => 'Status',
                    'label' => 'Update',
                    'location' => 'Location',
                ],
                'rows' => $trackingEvents,
                'empty' => 'No tracking events are available yet.',
            ]) ?>
        </div>
    <?php endif; ?>

    <?php if ($nextActionItems !== []): ?>
        <div class="section">
            <h2>Payment next action</h2>
            <?= $view->renderComponent('DefinitionGrid', ['items' => $nextActionItems]) ?>
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
