<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'UserModule',
        'headline' => $headline ?? 'Choose a new password',
        'summary' => $summary ?? '',
    ]) ?>

    <form method="post" action="/users/password/reset/<?= $view->escapeUrl((string) ($form['user'] ?? '0')) ?>/<?= $view->escapeUrl((string) ($form['token'] ?? '')) ?>">
        <label>New Password<br><input type="password" name="password" required></label>
        <label>Confirm New Password<br><input type="password" name="password_confirmation" required></label>
        <button type="submit">Reset password</button>
    </form>
</section>
