<?php

$orders = is_array($orders ?? null) ? $orders : [];
$order = is_array($order ?? null) ? $order : [];
$actions = is_array($order['actions'] ?? null) ? $order['actions'] : [];
$linkActions = [
    'admin_view' => 'Refresh admin view',
    'public_view' => 'Open native order surface',
    'complete_return' => 'Open payment completion return',
    'cancelled_return' => 'Open payment cancelled return',
    'continue_payment' => 'Continue provider payment',
];
$submitActions = [
    'capture' => 'Capture payment',
    'reconcile' => 'Reconcile payment',
    'refund' => 'Refund payment',
    'cancel' => 'Cancel order',
    'pack' => 'Pack order',
    'book_shipment' => 'Book shipment / label',
    'sync_tracking' => 'Sync tracking',
    'cancel_shipment' => 'Cancel shipment booking',
    'deliver' => 'Mark delivered',
];
$confirmActions = [
    'refund' => 'Refund this order payment?',
    'cancel' => 'Cancel this order and release its inventory?',
    'cancel_shipment' => 'Cancel this shipment booking and re-open fulfillment?',
    'deliver' => 'Mark this order as delivered?',
];
$trackingApps = is_array($order['tracking_apps'] ?? null) ? $order['tracking_apps'] : [];
$trackingEvents = is_array($order['tracking_events'] ?? null) ? $order['tracking_events'] : [];
$entitlements = is_array($order['entitlements'] ?? null) ? $order['entitlements'] : [];
$servicePoints = is_array($service_points ?? null) ? $service_points : [];
?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Orders',
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <?php if ($order !== []): ?>
        <div class="section">
            <h2>Selected order</h2>
            <?= $view->renderComponent('DefinitionGrid', [
                'items' => [
                    'Order number' => $order['order_number'] ?? '',
                    'User ID' => $order['user_id'] ?? 0,
                    'Cart ID' => $order['cart_id'] ?? 0,
                    'Contact' => $order['contact_email'] ?? '',
                    'Status' => $order['status'] ?? '',
                    'Fulfillment status' => $order['fulfillment_status'] ?? '',
                    'Inventory status' => $order['inventory_status'] ?? '',
                    'Payment status' => $order['payment_status'] ?? '',
                    'Payment driver' => $order['payment_driver'] ?? '',
                    'Payment method' => $order['payment_method'] ?? '',
                    'Payment flow' => $order['payment_flow'] ?? '',
                    'Reference' => $order['payment_reference'] ?? '',
                    'Provider reference' => $order['payment_provider_reference'] ?? '',
                    'Shipping country' => $order['shipping_country'] ?? '',
                    'Shipping zone' => $order['shipping_zone'] ?? '',
                    'Shipping option' => $order['shipping_option_label'] ?? '',
                    'Carrier' => $order['shipping_carrier_label'] ?? '',
                    'Promotion code' => $order['discount_code'] ?? '',
                    'Promotion' => $order['discount_label'] ?? '',
                    'Service' => $order['shipping_service_label'] ?? '',
                    'Service point' => $order['shipping_service_point_name'] ?? '',
                    'Shipment reference' => $order['shipment_reference'] ?? '',
                    'Label reference' => $order['shipment_label_reference'] ?? '',
                    'Label URL' => $order['shipment_label_url'] ?? '',
                    'Tracking number' => $order['tracking_number'] ?? '',
                    'Tracking portal' => $order['tracking_url'] ?? '',
                    'Shipped at' => $order['shipped_at'] ?? '',
                    'Delivered at' => $order['delivered_at'] ?? '',
                    'Subtotal' => $order['subtotal'] ?? '',
                    'Discount' => $order['discount'] ?? '',
                    'Shipping before discount' => $order['shipping_base'] ?? '',
                    'Shipping discount' => $order['shipping_discount'] ?? '',
                    'Shipping' => $order['shipping'] ?? '',
                    'Tax' => $order['tax'] ?? '',
                    'Total' => $order['total'] ?? '',
                    'Created' => $order['created_at'] ?? '',
                ],
            ]) ?>
        </div>

        <?php if ($actions !== []): ?>
            <div class="section">
                <h2>Available actions</h2>

                <?php foreach ($linkActions as $key => $label): ?>
                    <?php if (!empty($actions[$key])): ?>
                        <p><a href="<?= $view->escape((string) $actions[$key]) ?>"><?= $view->escape($label) ?></a></p>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php foreach ($submitActions as $key => $label): ?>
                    <?php if (!empty($actions[$key])): ?>
                        <form method="post" action="<?= $view->escape((string) $actions[$key]) ?>"<?php if (isset($confirmActions[$key])): ?> onsubmit="return confirm('<?= $view->escape($confirmActions[$key]) ?>');"<?php endif; ?>>
                            <button type="submit"><?= $view->escape($label) ?></button>
                        </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($actions['service_points'])): ?>
            <div class="section">
                <h2>Carrier service points</h2>
                <form method="post" action="<?= $view->escape((string) $actions['service_points']) ?>" class="stack">
                    <label>
                        Carrier
                        <select name="carrier_code">
                            <?php foreach ((array) ($order['available_carriers'] ?? []) as $carrier): ?>
                                <?php $entry = is_array($carrier) ? $carrier : []; ?>
                                <option value="<?= $view->escape((string) ($entry['code'] ?? '')) ?>"<?php if (($order['shipping_carrier'] ?? '') === ($entry['code'] ?? '')): ?> selected<?php endif; ?>>
                                    <?= $view->escape((string) ($entry['label'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Postal code
                        <input type="text" name="postal_code" value="">
                    </label>

                    <label>
                        City
                        <input type="text" name="city" value="">
                    </label>

                    <div>
                        <button type="submit">Lookup service points</button>
                    </div>
                </form>

                <?php if ($servicePoints !== []): ?>
                    <?= $view->renderComponent('DataTable', [
                        'columns' => [
                            'id' => 'ID',
                            'label' => 'Name',
                            'address_line' => 'Address',
                            'postal_code' => 'Postal code',
                            'city' => 'City',
                            'distance_meters' => 'Distance (m)',
                            'cutoff_time' => 'Cutoff',
                        ],
                        'rows' => $servicePoints,
                        'empty' => 'No service points were returned for this lookup.',
                    ]) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($actions['book_shipment'])): ?>
            <div class="section">
                <h2>Book shipment and label</h2>
                <form method="post" action="<?= $view->escape((string) $actions['book_shipment']) ?>" class="stack">
                    <label>
                        Carrier
                        <select name="carrier_code">
                            <?php foreach ((array) ($order['available_carriers'] ?? []) as $carrier): ?>
                                <?php $entry = is_array($carrier) ? $carrier : []; ?>
                                <option value="<?= $view->escape((string) ($entry['code'] ?? '')) ?>"<?php if (($order['shipping_carrier'] ?? '') === ($entry['code'] ?? '')): ?> selected<?php endif; ?>>
                                    <?= $view->escape((string) ($entry['label'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Service point ID
                        <input type="text" name="service_point_id" value="<?= $view->escape((string) ($order['shipping_service_point_id'] ?? '')) ?>">
                    </label>

                    <label>
                        Service point name
                        <input type="text" name="service_point_name" value="<?= $view->escape((string) ($order['shipping_service_point_name'] ?? '')) ?>">
                    </label>

                    <label>
                        Label format
                        <input type="text" name="label_format" value="pdf">
                    </label>

                    <div>
                        <button type="submit">Book shipment / create label reference</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($actions['ship'])): ?>
            <div class="section">
                <h2>Ship order</h2>
                <form method="post" action="<?= $view->escape((string) $actions['ship']) ?>" class="stack">
                    <label>
                        Carrier
                        <select name="carrier_code">
                            <?php foreach ((array) ($order['available_carriers'] ?? []) as $carrier): ?>
                                <?php $entry = is_array($carrier) ? $carrier : []; ?>
                                <option value="<?= $view->escape((string) ($entry['code'] ?? '')) ?>"<?php if (($order['shipping_carrier'] ?? '') === ($entry['code'] ?? '')): ?> selected<?php endif; ?>>
                                    <?= $view->escape((string) ($entry['label'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Tracking number
                        <input type="text" name="tracking_number" value="<?= $view->escape((string) ($order['tracking_number'] ?? '')) ?>">
                    </label>

                    <label>
                        Shipment reference
                        <input type="text" name="shipment_reference" value="<?= $view->escape((string) ($order['shipment_reference'] ?? '')) ?>">
                    </label>

                    <label>
                        Service point ID
                        <input type="text" name="service_point_id" value="<?= $view->escape((string) ($order['shipping_service_point_id'] ?? '')) ?>">
                    </label>

                    <label>
                        Service point name
                        <input type="text" name="service_point_name" value="<?= $view->escape((string) ($order['shipping_service_point_name'] ?? '')) ?>">
                    </label>

                    <label>
                        <input type="checkbox" name="book_label" value="1" checked>
                        Auto-book label if no tracking number is provided
                    </label>

                    <div>
                        <button type="submit">Ship order</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($actions['sync_tracking'])): ?>
            <div class="section">
                <h2>Sync tracking</h2>
                <form method="post" action="<?= $view->escape((string) $actions['sync_tracking']) ?>" class="stack">
                    <label>
                        Tracking status
                        <select name="tracking_status">
                            <?php foreach (['in_transit' => 'In transit', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'] as $value => $label): ?>
                                <option value="<?= $view->escape($value) ?>"><?= $view->escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Location
                        <input type="text" name="location" value="<?= $view->escape((string) ($order['shipping_service_point_name'] ?? $order['shipping_country'] ?? '')) ?>">
                    </label>

                    <div>
                        <button type="submit">Sync tracking status</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

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

        <div class="section">
            <h2>Order items</h2>
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

        <?php if ($entitlements !== []): ?>
            <div class="section">
                <h2>Purchased access</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Downloads</th>
                            <th>Access</th>
                            <th>Admin action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entitlements as $entitlement): ?>
                            <?php
                                $row = is_array($entitlement) ? $entitlement : [];
                                $limit = (int) ($row['download_limit'] ?? 0);
                                $used = (int) ($row['downloads_used'] ?? 0);
                                $status = (string) ($row['status'] ?? '');
                            ?>
                            <tr>
                                <td><?= $view->escape((string) ($row['label'] ?? '')) ?></td>
                                <td><?= $view->escape((string) ($row['type'] ?? '')) ?></td>
                                <td><?= $view->escape($status) ?></td>
                                <td><?= $view->escape($limit > 0 ? $used . ' / ' . $limit : 'Unlimited') ?></td>
                                <td>
                                    <?php if (!empty($row['access_path'])): ?>
                                        <a href="<?= $view->escape((string) $row['access_path']) ?>">Open access surface</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status === 'active' && !empty($row['revoke_path'])): ?>
                                        <form method="post" action="<?= $view->escape((string) $row['revoke_path']) ?>" onsubmit="return confirm('Revoke this purchased access?');">
                                            <button type="submit">Revoke</button>
                                        </form>
                                    <?php elseif ($status !== 'active' && !empty($row['activate_path'])): ?>
                                        <form method="post" action="<?= $view->escape((string) $row['activate_path']) ?>">
                                            <button type="submit">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>Stored addresses</h2>
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
    <?php endif; ?>

    <div class="section">
        <h2>Order history</h2>
        <?php if ($orders === []): ?>
            <p>No orders are available.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Fulfillment</th>
                        <th>Promo</th>
                        <th>Carrier</th>
                        <th>Payment</th>
                        <th>Driver</th>
                        <th>Total</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $entry): ?>
                        <?php $row = is_array($entry) ? $entry : []; ?>
                        <tr>
                            <td><?= (int) ($row['id'] ?? 0) ?></td>
                            <td><?= $view->escape((string) ($row['order_number'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['contact_email'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['status'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['fulfillment_status'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['discount_code'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['shipping_carrier_label'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['payment_status'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['payment_driver'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['total'] ?? '')) ?></td>
                            <td>
                                <?php if (!empty($row['view_path'])): ?>
                                    <a href="<?= $view->escape((string) $row['view_path']) ?>">Open admin view</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
