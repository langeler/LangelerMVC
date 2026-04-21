<?php

$categories = is_array($categories ?? null) ? $categories : [];
$products = is_array($products ?? null) ? $products : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];
$filters = is_array($filters ?? null) ? $filters : [];
$category = is_array($category ?? null) ? $category : [];
$currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
$lastPage = max(1, (int) ($pagination['last_page'] ?? 1));
$total = max(0, (int) ($pagination['total'] ?? count($products)));
$formAction = (string) ($filters['form_action'] ?? '/shop');
$clearUrl = (string) ($filters['clear_url'] ?? '/shop');
$queryForPage = static function (int $page) use ($filters): string {
    $query = [
        'q' => trim((string) ($filters['q'] ?? '')),
        'availability' => (string) ($filters['availability'] ?? 'all'),
        'sort' => (string) ($filters['sort'] ?? 'newest'),
    ];

    if ($page > 1) {
        $query['page'] = (string) $page;
    }

    return http_build_query(array_filter(
        $query,
        static fn(mixed $value): bool => $value !== '' && $value !== 'all' && $value !== 'newest'
    ));
};
?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'ShopModule',
        'headline' => $headline ?? 'Shop catalog',
        'summary' => $summary ?? '',
    ]) ?>

    <div class="section">
        <h2>Refine storefront</h2>
        <form method="get" action="<?= $view->escape($formAction) ?>" class="stack">
            <label>
                Search catalog
                <input type="search" name="q" value="<?= $view->escape((string) ($filters['q'] ?? '')) ?>" placeholder="Search products">
            </label>

            <label>
                Availability
                <select name="availability">
                    <?php foreach ([
                        'all' => 'All products',
                        'in_stock' => 'In stock',
                        'out_of_stock' => 'Out of stock',
                    ] as $value => $label): ?>
                        <option value="<?= $view->escape($value) ?>"<?php if (($filters['availability'] ?? 'all') === $value): ?> selected<?php endif; ?>>
                            <?= $view->escape($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Sort by
                <select name="sort">
                    <?php foreach ([
                        'newest' => 'Newest first',
                        'oldest' => 'Oldest first',
                        'name' => 'Name',
                        'price_low' => 'Price: low to high',
                        'price_high' => 'Price: high to low',
                    ] as $value => $label): ?>
                        <option value="<?= $view->escape($value) ?>"<?php if (($filters['sort'] ?? 'newest') === $value): ?> selected<?php endif; ?>>
                            <?= $view->escape($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div>
                <button type="submit">Apply filters</button>
                <a href="<?= $view->escape($clearUrl) ?>">Clear filters</a>
            </div>
        </form>
    </div>

    <div class="section">
        <h2>Categories</h2>

        <?php if ($categories === []): ?>
            <p>No categories have been published yet.</p>
        <?php else: ?>
            <ul class="badge-list">
                <li><a href="/shop">All catalog</a></li>
                <?php foreach ($categories as $categoryItem): ?>
                    <?php $entry = is_array($categoryItem) ? $categoryItem : []; ?>
                    <?php $url = (string) ($entry['url'] ?? '/shop'); ?>
                    <li>
                        <a href="<?= $view->escape($url) ?>">
                            <?= $view->escape((string) ($entry['name'] ?? 'Category')) ?>
                        </a>
                        <?php if (!empty($entry['is_active'])): ?>
                            <strong>(Active)</strong>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Catalog snapshot</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Selected category' => $category['name'] ?? 'All catalog',
                'Search' => ($filters['q'] ?? '') !== '' ? $filters['q'] : 'No search filter',
                'Availability' => match ((string) ($filters['availability'] ?? 'all')) {
                    'in_stock' => 'In stock only',
                    'out_of_stock' => 'Out of stock only',
                    default => 'All products',
                },
                'Sort order' => match ((string) ($filters['sort'] ?? 'newest')) {
                    'oldest' => 'Oldest first',
                    'name' => 'Name',
                    'price_low' => 'Price: low to high',
                    'price_high' => 'Price: high to low',
                    default => 'Newest first',
                },
                'Results' => $total,
            ],
        ]) ?>
    </div>

    <div class="section">
        <h2>Products</h2>
        <?= $view->renderComponent('ProductGrid', [
            'products' => $products,
            'empty' => 'No products are currently published.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Pagination</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Current page' => $currentPage,
                'Last page' => $lastPage,
                'Per page' => $pagination['per_page'] ?? 12,
                'Total products' => $total,
            ],
            'empty' => 'No pagination data available.',
        ]) ?>

        <?php if ($lastPage > 1): ?>
            <p>
                <?php if ($currentPage > 1): ?>
                    <?php $previousQuery = $queryForPage($currentPage - 1); ?>
                    <a href="<?= $view->escape($formAction . ($previousQuery !== '' ? '?' . $previousQuery : '')) ?>">Previous page</a>
                <?php endif; ?>

                <?php if ($currentPage < $lastPage): ?>
                    <?php if ($currentPage > 1): ?> | <?php endif; ?>
                    <?php $nextQuery = $queryForPage($currentPage + 1); ?>
                    <a href="<?= $view->escape($formAction . ($nextQuery !== '' ? '?' . $nextQuery : '')) ?>">Next page</a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</section>
