<?php $product = is_array($product ?? null) ? $product : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'ShopModule',
        'headline' => $headline ?? ($product['name'] ?? 'Product'),
        'summary' => $summary ?? '',
    ]) ?>

    <div class="section">
        <h2>Product details</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Name' => $product['name'] ?? '',
                'Price' => $product['price'] ?? '',
                'Stock' => $product['stock'] ?? '',
                'Slug' => $product['slug'] ?? '',
                'Description' => $product['description'] ?? '',
            ],
        ]) ?>
    </div>

    <div class="section">
        <h2>Related products</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'name' => 'Product',
                'price' => 'Price',
                'stock' => 'Stock',
            ],
            'rows' => is_array($related ?? null) ? $related : [],
            'empty' => 'No related products were found.',
        ]) ?>
    </div>
</section>
