<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Choose a new password'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <form method="post" action="/users/password/reset/<?= urlencode((string) ($form['user'] ?? '0')) ?>/<?= urlencode((string) ($form['token'] ?? '')) ?>">
        <p><label>New Password<br><input type="password" name="password" required></label></p>
        <p><label>Confirm New Password<br><input type="password" name="password_confirmation" required></label></p>
        <p><button type="submit">Reset password</button></p>
    </form>
</section>
