<?php
declare(strict_types=1);
?>
<section class="stack">
    <?= $view->renderPartial(...(array) ['PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Admin dashboard',
        'summary' => $summary ?? '',
    ]]); ?>

    <div class="section">
        <h2>Metrics</h2>
        <?= $view->renderComponent(...(array) ['DefinitionGrid', [
            'items' => is_array($metrics ?? null) ? $metrics : [],
            'empty' => 'No administrative metrics are available yet.',
        ]]); ?>
    </div>

    <div class="section">
        <h2>Management</h2>
        <?= $view->renderComponent(...(array) ['LinkList', [
            'links' => [
                ['href' => '/admin/users', 'label' => 'Users'],
                ['href' => '/admin/roles', 'label' => 'Roles'],
                ['href' => '/admin/pages', 'label' => 'Pages'],
                ['href' => '/admin/catalog', 'label' => 'Catalog'],
                ['href' => '/admin/promotions', 'label' => 'Promotions'],
                ['href' => '/admin/carts', 'label' => 'Carts'],
                ['href' => '/admin/orders', 'label' => 'Orders'],
                ['href' => '/admin/system', 'label' => 'System'],
                ['href' => '/admin/operations', 'label' => 'Operations'],
            ],
        ]]); ?>
    </div>
</section>
