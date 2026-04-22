<?php

$categories = is_array($categories ?? null) ? $categories : [];
$catalog = is_array($catalog ?? null) ? $catalog : [];
$categoryForm = is_array($category_form ?? null) ? $category_form : [];
$productForm = is_array($product_form ?? null) ? $product_form : [];
$catalogMetrics = is_array($catalog_metrics ?? null) ? $catalog_metrics : [];
?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Catalog',
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <div class="section">
        <h2>Catalog metrics</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => $catalogMetrics,
            'empty' => 'No catalog metrics are available.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Create category</h2>
        <form method="post" action="/admin/catalog/categories" class="stack">
            <label>
                Category name
                <input type="text" name="name" value="<?= $view->escape((string) ($categoryForm['name'] ?? '')) ?>" required>
            </label>

            <label>
                Category slug
                <input type="text" name="slug" value="<?= $view->escape((string) ($categoryForm['slug'] ?? '')) ?>" placeholder="Optional: generated from the name">
            </label>

            <label>
                Description
                <textarea name="description" rows="3"><?= $view->escape((string) ($categoryForm['description'] ?? '')) ?></textarea>
            </label>

            <label>
                Published
                <select name="is_published">
                    <option value="1"<?php if (!empty($categoryForm['is_published'])): ?> selected<?php endif; ?>>Published</option>
                    <option value="0"<?php if (array_key_exists('is_published', $categoryForm) && empty($categoryForm['is_published'])): ?> selected<?php endif; ?>>Draft</option>
                </select>
            </label>

            <div>
                <button type="submit">Create category</button>
            </div>
        </form>
    </div>

    <div class="section">
        <h2>Categories</h2>
        <?php if ($categories === []): ?>
            <p>No categories are available.</p>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <?php $entry = is_array($category) ? $category : []; ?>
                <article class="section">
                    <h3><?= $view->escape((string) ($entry['name'] ?? 'Category')) ?></h3>
                    <?= $view->renderComponent('DefinitionGrid', [
                        'items' => [
                            'ID' => $entry['id'] ?? 0,
                            'Slug' => $entry['slug'] ?? '',
                            'Status' => $entry['status'] ?? '',
                            'Storefront' => $entry['storefront_path'] ?? '',
                            'Description' => $entry['description'] ?? '',
                        ],
                    ]) ?>

                    <form method="post" action="<?= $view->escape((string) ($entry['update_path'] ?? '/admin/catalog')) ?>" class="stack">
                        <label>
                            Name
                            <input type="text" name="name" value="<?= $view->escape((string) ($entry['name'] ?? '')) ?>" required>
                        </label>

                        <label>
                            Slug
                            <input type="text" name="slug" value="<?= $view->escape((string) ($entry['slug'] ?? '')) ?>">
                        </label>

                        <label>
                            Description
                            <textarea name="description" rows="3"><?= $view->escape((string) ($entry['description'] ?? '')) ?></textarea>
                        </label>

                        <label>
                            Published
                            <select name="is_published">
                                <option value="1"<?php if (!empty($entry['is_published'])): ?> selected<?php endif; ?>>Published</option>
                                <option value="0"<?php if (array_key_exists('is_published', $entry) && empty($entry['is_published'])): ?> selected<?php endif; ?>>Draft</option>
                            </select>
                        </label>

                        <div>
                            <button type="submit">Save category</button>
                            <?php if (!empty($entry['storefront_path'])): ?>
                                <a href="<?= $view->escape((string) $entry['storefront_path']) ?>">Open storefront category</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div>
                        <?php if (!empty($entry['is_published'])): ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['unpublish_path'] ?? '')) ?>">
                                <button type="submit">Unpublish category</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['publish_path'] ?? '')) ?>">
                                <button type="submit">Publish category</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="<?= $view->escape((string) ($entry['delete_path'] ?? '')) ?>" onsubmit="return confirm('Delete this category? This cannot be undone.');">
                            <button type="submit">Delete category</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Create product</h2>
        <form method="post" action="/admin/catalog/products" class="stack">
            <label>
                Category
                <select name="category_id">
                    <?php foreach ($categories as $category): ?>
                        <?php $entry = is_array($category) ? $category : []; ?>
                        <option value="<?= (int) ($entry['id'] ?? 0) ?>"<?php if ((int) ($productForm['category_id'] ?? 0) === (int) ($entry['id'] ?? 0)): ?> selected<?php endif; ?>>
                            <?= $view->escape((string) ($entry['name'] ?? 'Category')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Product name
                <input type="text" name="name" value="<?= $view->escape((string) ($productForm['name'] ?? '')) ?>" required>
            </label>

            <label>
                Product slug
                <input type="text" name="slug" value="<?= $view->escape((string) ($productForm['slug'] ?? '')) ?>" placeholder="Optional: generated from the name">
            </label>

            <label>
                Description
                <textarea name="description" rows="4"><?= $view->escape((string) ($productForm['description'] ?? '')) ?></textarea>
            </label>

            <label>
                Price minor
                <input type="number" name="price_minor" min="0" value="<?= (int) ($productForm['price_minor'] ?? 0) ?>">
            </label>

            <label>
                Currency
                <input type="text" name="currency" value="<?= $view->escape((string) ($productForm['currency'] ?? 'SEK')) ?>">
            </label>

            <label>
                Visibility
                <select name="visibility">
                    <?php foreach (['published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label): ?>
                        <option value="<?= $view->escape($value) ?>"<?php if (($productForm['visibility'] ?? 'published') === $value): ?> selected<?php endif; ?>>
                            <?= $view->escape($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Stock
                <input type="number" name="stock" min="0" value="<?= (int) ($productForm['stock'] ?? 0) ?>">
            </label>

            <label>
                Media paths
                <textarea name="media" rows="3" placeholder="/assets/images/example.svg, /assets/images/example-2.svg"><?= $view->escape((string) ($productForm['media'] ?? '')) ?></textarea>
            </label>

            <div>
                <button type="submit">Create product</button>
            </div>
        </form>
    </div>

    <div class="section">
        <h2>Products</h2>
        <?php if ($catalog === []): ?>
            <p>No products are available.</p>
        <?php else: ?>
            <?php foreach ($catalog as $product): ?>
                <?php $entry = is_array($product) ? $product : []; ?>
                <article class="section">
                    <h3><?= $view->escape((string) ($entry['name'] ?? 'Product')) ?></h3>
                    <?= $view->renderComponent('DefinitionGrid', [
                        'items' => [
                            'ID' => $entry['id'] ?? 0,
                            'Category' => $entry['category'] ?? '',
                            'Slug' => $entry['slug'] ?? '',
                            'Visibility' => $entry['status'] ?? ($entry['visibility'] ?? ''),
                            'Price' => $entry['price'] ?? '',
                            'Stock' => $entry['stock'] ?? 0,
                            'Storefront' => $entry['storefront_path'] ?? '',
                        ],
                    ]) ?>

                    <form method="post" action="<?= $view->escape((string) ($entry['update_path'] ?? '/admin/catalog')) ?>" class="stack">
                        <label>
                            Category
                            <select name="category_id">
                                <?php foreach ($categories as $category): ?>
                                    <?php $categoryEntry = is_array($category) ? $category : []; ?>
                                    <option value="<?= (int) ($categoryEntry['id'] ?? 0) ?>"<?php if ((int) ($entry['category_id'] ?? 0) === (int) ($categoryEntry['id'] ?? 0)): ?> selected<?php endif; ?>>
                                        <?= $view->escape((string) ($categoryEntry['name'] ?? 'Category')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Name
                            <input type="text" name="name" value="<?= $view->escape((string) ($entry['name'] ?? '')) ?>" required>
                        </label>

                        <label>
                            Slug
                            <input type="text" name="slug" value="<?= $view->escape((string) ($entry['slug'] ?? '')) ?>">
                        </label>

                        <label>
                            Description
                            <textarea name="description" rows="4"><?= $view->escape((string) ($entry['description'] ?? '')) ?></textarea>
                        </label>

                        <label>
                            Price minor
                            <input type="number" name="price_minor" min="0" value="<?= (int) ($entry['price_minor'] ?? 0) ?>">
                        </label>

                        <label>
                            Currency
                            <input type="text" name="currency" value="<?= $view->escape((string) ($entry['currency'] ?? 'SEK')) ?>">
                        </label>

                        <label>
                            Visibility
                            <select name="visibility">
                                <?php foreach (['published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label): ?>
                                    <option value="<?= $view->escape($value) ?>"<?php if (($entry['visibility'] ?? 'published') === $value): ?> selected<?php endif; ?>>
                                        <?= $view->escape($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Stock
                            <input type="number" name="stock" min="0" value="<?= (int) ($entry['stock'] ?? 0) ?>">
                        </label>

                        <label>
                            Media paths
                            <textarea name="media" rows="3"><?= $view->escape((string) ($entry['media_input'] ?? '')) ?></textarea>
                        </label>

                        <div>
                            <button type="submit">Save product</button>
                            <?php if (!empty($entry['storefront_path'])): ?>
                                <a href="<?= $view->escape((string) $entry['storefront_path']) ?>">Open storefront product</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div>
                        <?php if (($entry['visibility'] ?? '') !== 'published'): ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['publish_path'] ?? '')) ?>">
                                <button type="submit">Publish product</button>
                            </form>
                        <?php endif; ?>

                        <?php if (($entry['visibility'] ?? '') !== 'draft'): ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['draft_path'] ?? '')) ?>">
                                <button type="submit">Move to draft</button>
                            </form>
                        <?php endif; ?>

                        <?php if (($entry['visibility'] ?? '') !== 'archived'): ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['archive_path'] ?? '')) ?>" onsubmit="return confirm('Archive this product and remove it from the storefront?');">
                                <button type="submit">Archive product</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="<?= $view->escape((string) ($entry['delete_path'] ?? '')) ?>" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                            <button type="submit">Delete product</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
