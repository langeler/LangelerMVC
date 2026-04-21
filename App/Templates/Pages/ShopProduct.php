<?php $product = is_array($product ?? null) ? $product : []; ?>
<?php $media = is_array($product['media'] ?? null) ? $product['media'] : []; ?>
<?php $category = is_array($category ?? null) ? $category : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'ShopModule',
        'headline' => $headline ?? ($product['name'] ?? 'Product'),
        'summary' => $summary ?? '',
    ]) ?>

    <?php if ($product === []): ?>
        <div class="section">
            <h2>Storefront fallback</h2>
            <p>The requested product could not be loaded from the published catalog.</p>
            <p><a href="/shop">Return to the shop catalog</a></p>
        </div>
    <?php else: ?>
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
                'Availability' => $product['availability'] ?? '',
                'Stock' => $product['stock'] ?? '',
                'Slug' => $product['slug'] ?? '',
                'Category' => $category['name'] ?? 'Uncategorized',
                'Description' => $product['description'] ?? '',
            ],
        ]) ?>
    </div>

    <div class="section">
        <h2>Purchase options</h2>

        <?php if (!empty($category['url'])): ?>
            <p><a href="<?= $view->escape((string) $category['url']) ?>">Browse more in <?= $view->escape((string) ($category['name'] ?? 'this category')) ?></a></p>
        <?php endif; ?>

        <?php if (!empty($product['is_in_stock'])): ?>
            <form method="post" action="/cart/items" class="stack">
                <input type="hidden" name="slug" value="<?= $view->escape((string) ($product['slug'] ?? '')) ?>">

                <label>
                    Quantity
                    <input type="number" name="quantity" min="1" max="<?= (int) ($product['stock'] ?? 1) ?>" value="1">
                </label>

                <div>
                    <button type="submit">Add to cart</button>
                    <a href="/cart">Review cart</a>
                </div>
            </form>
        <?php else: ?>
            <p>This product is currently out of stock and cannot be added to new carts.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Related products</h2>
        <?= $view->renderComponent('ProductGrid', [
            'products' => is_array($related ?? null) ? $related : [],
            'empty' => 'No related products were found.',
        ]) ?>
    </div>
    <?php endif; ?>
</section>
