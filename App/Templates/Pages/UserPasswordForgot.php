<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'UserModule',
        'headline' => $headline ?? 'Reset password',
        'summary' => $summary ?? '',
    ]) ?>

    <form method="post" action="/users/password/forgot">
        <label>Email<br><input type="email" name="email" required></label>
        <button type="submit">Send reset token</button>
    </form>
</section>
