<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Orders',
        'summary' => $summary ?? '',
    ]) ?>

    <?= $view->renderComponent('DataTable', [
        'columns' => [
            'id' => 'ID',
            'order_number' => 'Order',
            'contact_email' => 'Email',
            'status' => 'Status',
            'payment_status' => 'Payment',
            'payment_driver' => 'Driver',
            'total' => 'Total',
        ],
        'rows' => is_array($orders ?? null) ? $orders : [],
        'empty' => 'No orders are available.',
    ]) ?>
</section>
