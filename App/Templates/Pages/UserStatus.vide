<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'UserModule',
        'headline' => $headline ?? 'Status',
        'summary' => $summary ?? '',
    ]) ?>

    <?= $view->renderPartial('StatusMessage', ['message' => $message ?? '']) ?>

    <?php if (is_array($user ?? null)): ?>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'User' => $user['email'] ?? '',
            ],
        ]) ?>
    <?php endif; ?>

    <?php if (is_array($otp ?? null) && ($otp['uri'] ?? '') !== ''): ?>
        <div class="section">
            <h2>Provisioning URI</h2>
            <?= $view->renderComponent('CodeList', ['items' => [$otp['uri']]]) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($recoveryCodes ?? [])): ?>
        <div class="section">
            <h2>Recovery Codes</h2>
            <?= $view->renderComponent('CodeList', ['items' => $recoveryCodes]) ?>
        </div>
    <?php endif; ?>

    <?= $view->renderComponent('LinkList', [
        'links' => [
            ['href' => '/users/profile', 'label' => 'Profile'],
            ['href' => '/users/login', 'label' => 'Sign in'],
            ['href' => '/users/register', 'label' => 'Register'],
        ],
    ]) ?>
</section>
