<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Status'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (($message ?? '') !== ''): ?>
        <p><strong><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>
    <?php if (is_array($user ?? null)): ?>
        <p>User: <?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (is_array($otp ?? null) && ($otp['uri'] ?? '') !== ''): ?>
        <p>Provisioning URI: <code><?= htmlspecialchars((string) $otp['uri'], ENT_QUOTES, 'UTF-8') ?></code></p>
    <?php endif; ?>
    <?php if (!empty($recoveryCodes ?? [])): ?>
        <h2>Recovery Codes</h2>
        <ul>
            <?php foreach (($recoveryCodes ?? []) as $code): ?>
                <li><code><?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8') ?></code></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <p><a href="/users/profile">Profile</a> · <a href="/users/login">Sign in</a> · <a href="/users/register">Register</a></p>
</section>
