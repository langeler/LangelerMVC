<?php

$products = is_array($products ?? null) ? $products : [];
$empty = (string) ($empty ?? '');
$headingLevel = (int) ($headingLevel ?? 3);
$headingTag = $headingLevel >= 2 && $headingLevel <= 6 ? 'h' . $headingLevel : 'h3';

if ($products === []) {
    if ($empty !== ''): ?>
        <p><?= $view->escape($empty) ?></p>
    <?php endif;

    return;
}
?>
<div class="product-grid">
    <?php foreach ($products as $product): ?>
        <?php
        $entry = is_array($product) ? $product : [];
        $media = is_array($entry['media'] ?? null)
            ? array_values(array_filter(array_map(static fn(mixed $item): string => (string) $item, $entry['media'])))
            : [];
        $primaryMedia = $media[0] ?? '';
        $name = (string) ($entry['name'] ?? 'Product');
        $slug = (string) ($entry['slug'] ?? '');
        $description = (string) ($entry['description'] ?? '');
        $price = (string) ($entry['price'] ?? '');
        $stock = (string) ($entry['stock'] ?? '');
        ?>
        <article class="product-card">
            <?php if ($primaryMedia !== ''): ?>
                <div class="product-card__media">
                    <img src="<?= $view->escape($primaryMedia) ?>" alt="<?= $view->escape($name) ?>">
                </div>
            <?php endif; ?>

            <div class="product-card__body">
                <?php if ($stock !== ''): ?>
                    <p class="product-card__eyebrow">Stock <?= $view->escape($stock) ?></p>
                <?php endif; ?>

                <<?= $headingTag ?> class="product-card__title"><?= $view->escape($name) ?></<?= $headingTag ?>>

                <?php if ($description !== ''): ?>
                    <p class="product-card__summary"><?= $view->escape($description) ?></p>
                <?php endif; ?>

                <div class="product-card__meta">
                    <?php if ($price !== ''): ?>
                        <strong><?= $view->escape($price) ?></strong>
                    <?php endif; ?>

                    <?php if ($slug !== ''): ?>
                        <span><?= $view->escape('/shop/products/' . $slug) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($slug !== ''): ?>
                    <a class="product-card__link" href="/shop/products/<?= $view->escape($slug) ?>">View product</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
