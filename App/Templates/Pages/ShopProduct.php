<?php $product = is_array($product ?? null) ? $product : []; ?>
<?php $media = is_array($product['media'] ?? null) ? $product['media'] : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'ShopModule',
        'headline' => $headline ?? ($product['name'] ?? 'Product'),
        'summary' => $summary ?? '',
    ]) ?>

    <?php if ($media !== []): ?>
        <div class="section">
            <h2>Product media</h2>
            <div class="product-hero">
                <img src="<?= $view->escape((string) $media[0]) ?>" alt="<?= $view->escape((string) ($product['name'] ?? 'Product')) ?>">
            </div>
        </div>
    <?php endif; ?>

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
        <?= $view->renderComponent('ProductGrid', [
            'products' => is_array($related ?? null) ? $related : [],
            'empty' => 'No related products were found.',
        ]) ?>
    </div>
</section>
