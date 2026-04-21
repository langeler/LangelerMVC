<?php

$orders = is_array($orders ?? null) ? $orders : [];
$order = is_array($order ?? null) ? $order : [];
$actions = is_array($order['actions'] ?? null) ? $order['actions'] : [];
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
                    'Payment status' => $order['payment_status'] ?? '',
                    'Payment driver' => $order['payment_driver'] ?? '',
                    'Payment method' => $order['payment_method'] ?? '',
                    'Payment flow' => $order['payment_flow'] ?? '',
                    'Reference' => $order['payment_reference'] ?? '',
                    'Provider reference' => $order['payment_provider_reference'] ?? '',
                    'Total' => $order['total'] ?? '',
                    'Created' => $order['created_at'] ?? '',
                ],
            ]) ?>
        </div>

        <?php if ($actions !== []): ?>
            <div class="section">
                <h2>Available actions</h2>

                <?php foreach ([
                    'admin_view' => 'Refresh admin view',
                    'public_view' => 'Open native order surface',
                    'complete_return' => 'Open payment completion return',
                    'cancelled_return' => 'Open payment cancelled return',
                    'continue_payment' => 'Continue provider payment',
                ] as $key => $label): ?>
                    <?php if (!empty($actions[$key])): ?>
                        <p><a href="<?= $view->escape((string) $actions[$key]) ?>"><?= $view->escape($label) ?></a></p>
                    <?php endif; ?>
                <?php endforeach; ?>

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
                        <th>Payment</th>
                        <th>Method</th>
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
                            <td><?= $view->escape((string) ($row['payment_status'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($row['payment_method'] ?? '')) ?></td>
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
