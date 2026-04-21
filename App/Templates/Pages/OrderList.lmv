<?php $orders = is_array($orders ?? null) ? $orders : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'OrderModule',
        'headline' => $headline ?? 'Orders',
        'summary' => $summary ?? '',
    ]) ?>

    <div class="section">
        <h2>Order history</h2>
        <?php if ($orders === []): ?>
            <p>No orders have been created yet.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php $entry = is_array($order) ? $order : []; ?>
                        <tr>
                            <td><?= $view->escape((string) ($entry['order_number'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($entry['status'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($entry['payment_status'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($entry['total'] ?? '')) ?></td>
                            <td>
                                <?php if (!empty($entry['view_path'])): ?>
                                    <a href="<?= $view->escape((string) $entry['view_path']) ?>">Open order</a>
                                <?php else: ?>
                                    <span>Unavailable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
