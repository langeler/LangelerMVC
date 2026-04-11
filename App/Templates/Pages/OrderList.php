<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'OrderModule',
        'headline' => $headline ?? 'Orders',
        'summary' => $summary ?? '',
    ]) ?>

    <div class="section">
        <h2>Order history</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'order_number' => 'Order',
                'status' => 'Status',
                'payment_status' => 'Payment',
                'total' => 'Total',
            ],
            'rows' => is_array($orders ?? null) ? $orders : [],
            'empty' => 'No orders have been created yet.',
        ]) ?>
    </div>
</section>
