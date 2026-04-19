<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Catalog',
        'summary' => $summary ?? '',
    ]) ?>

    <div class="section">
        <h2>Categories</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'id' => 'ID',
                'name' => 'Name',
                'slug' => 'Slug',
                'description' => 'Description',
            ],
            'rows' => is_array($categories ?? null) ? $categories : [],
            'empty' => 'No categories are available.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Products</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'id' => 'ID',
                'name' => 'Name',
                'category' => 'Category',
                'visibility' => 'Visibility',
                'price' => 'Price',
                'stock' => 'Stock',
            ],
            'rows' => is_array($catalog ?? null) ? $catalog : [],
            'empty' => 'No products are available.',
        ]) ?>
    </div>
</section>
