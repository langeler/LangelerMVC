<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'ShopModule',
        'headline' => $headline ?? 'Shop catalog',
        'summary' => $summary ?? '',
    ]) ?>

    <div class="section">
        <h2>Categories</h2>
        <?= $view->renderComponent('BadgeList', [
            'items' => array_map(
                static fn(array $category): string => (string) ($category['name'] ?? ''),
                is_array($categories ?? null) ? $categories : []
            ),
            'empty' => 'No categories have been published yet.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Products</h2>
        <?= $view->renderComponent('ProductGrid', [
            'products' => is_array($products ?? null) ? $products : [],
            'empty' => 'No products are currently published.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Pagination</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => is_array($pagination ?? null) ? $pagination : [],
            'empty' => 'No pagination data available.',
        ]) ?>
    </div>
</section>
