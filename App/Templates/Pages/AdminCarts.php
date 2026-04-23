<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Carts',
        'summary' => $summary ?? '',
    ]) ?>

    <?= $view->renderComponent('DataTable', [
        'columns' => [
            'id' => 'ID',
            'user_id' => 'User',
            'status' => 'Status',
            'currency' => 'Currency',
            'items' => 'Items',
            'discount_code' => 'Promo',
            'discount' => 'Discount',
            'subtotal' => 'Subtotal',
            'total' => 'Total',
            'session_key' => 'Session Key',
        ],
        'rows' => is_array($carts ?? null) ? $carts : [],
        'empty' => 'No carts are available.',
    ]) ?>
</section>
