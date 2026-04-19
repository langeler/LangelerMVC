<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Users',
        'summary' => $summary ?? '',
    ]) ?>

    <?= $view->renderComponent('DataTable', [
        'columns' => [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'roles' => 'Roles',
            'permissions' => 'Permissions',
        ],
        'rows' => is_array($users ?? null) ? $users : [],
        'empty' => 'No users are available.',
    ]) ?>
</section>
