<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Reset password'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <form method="post" action="/users/password/forgot">
        <p><label>Email<br><input type="email" name="email" required></label></p>
        <p><button type="submit">Send reset token</button></p>
    </form>
</section>
