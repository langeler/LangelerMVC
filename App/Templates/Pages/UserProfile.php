<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Profile'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (is_array($user ?? null)): ?>
        <dl>
            <dt>Name</dt><dd><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Email</dt><dd><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Status</dt><dd><?= htmlspecialchars((string) ($user['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Email Verified</dt><dd><?= !empty($user['emailVerified']) ? 'Yes' : 'No' ?></dd>
            <dt>OTP Enabled</dt><dd><?= !empty($user['otpEnabled']) ? 'Yes' : 'No' ?></dd>
        </dl>
    <?php endif; ?>
    <h2>Update profile</h2>
    <form method="post" action="/users/profile">
        <p><label>Name<br><input type="text" name="name" value="<?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></label></p>
        <p><label>Email<br><input type="email" name="email" value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></label></p>
        <p><button type="submit">Save profile</button></p>
    </form>
    <h2>Change password</h2>
    <form method="post" action="/users/password/change">
        <p><label>Current Password<br><input type="password" name="current_password" required></label></p>
        <p><label>New Password<br><input type="password" name="password" required></label></p>
        <p><label>Confirm New Password<br><input type="password" name="password_confirmation" required></label></p>
        <p><button type="submit">Change password</button></p>
    </form>
    <h2>Roles</h2>
    <p><?= htmlspecialchars(implode(', ', $roles ?? []), ENT_QUOTES, 'UTF-8') ?></p>
    <h2>Permissions</h2>
    <p><?= htmlspecialchars(implode(', ', $permissions ?? []), ENT_QUOTES, 'UTF-8') ?></p>
    <h2>OTP</h2>
    <form method="post" action="/users/otp/enable">
        <p><button type="submit">Provision OTP</button></p>
    </form>
    <form method="post" action="/users/otp/verify">
        <p><label>Current OTP Code<br><input type="text" name="otp_code" inputmode="numeric"></label></p>
        <p><button type="submit">Verify OTP</button></p>
    </form>
    <form method="post" action="/users/otp/recovery-codes/regenerate">
        <p><button type="submit">Regenerate Recovery Codes</button></p>
    </form>
    <form method="post" action="/users/otp/disable">
        <p><button type="submit">Disable OTP</button></p>
    </form>
    <?php if (!empty($recoveryCodes ?? [])): ?>
        <h3>Recovery Codes</h3>
        <ul>
            <?php foreach (($recoveryCodes ?? []) as $code): ?>
                <li><code><?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8') ?></code></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <h2>Passkeys</h2>
    <p>
        <?php if (!empty($passkeySupport['available'])): ?>
            Driver: <?= htmlspecialchars((string) ($passkeySupport['driver'] ?? 'unknown'), ENT_QUOTES, 'UTF-8') ?>
        <?php else: ?>
            Passkeys are not available in the current runtime.
        <?php endif; ?>
    </p>
    <?php if (!empty($passkeys ?? [])): ?>
        <ul>
            <?php foreach (($passkeys ?? []) as $passkey): ?>
                <li>
                    <strong><?= htmlspecialchars((string) ($passkey['name'] ?? 'Passkey'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span> • Counter <?= htmlspecialchars((string) ($passkey['counter'] ?? 0), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (($passkey['lastUsedAt'] ?? '') !== ''): ?>
                        <span> • Last used <?= htmlspecialchars((string) $passkey['lastUsedAt'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <form method="post" action="/users/passkeys/<?= (int) ($passkey['id'] ?? 0) ?>/delete" style="display:inline">
                        <button type="submit">Remove</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No passkeys are registered for this account yet.</p>
    <?php endif; ?>
    <p><label>Passkey Name<br><input type="text" id="passkey-name" name="passkey_name" placeholder="My phone passkey"></label></p>
    <p><button type="button" id="register-passkey-button">Register Passkey</button></p>
    <form method="post" action="/users/email/verify">
        <p><button type="submit">Resend verification email</button></p>
    </form>
    <form method="post" action="/users/logout">
        <p><button type="submit">Sign out</button></p>
    </form>
</section>
<script>
    (() => {
        const button = document.getElementById('register-passkey-button');

        if (!button) {
            return;
        }

        if (!window.LangelerPasskeys || !window.LangelerPasskeys.isSupported()) {
            button.disabled = true;
            button.textContent = 'Passkeys unavailable';
            return;
        }

        button.addEventListener('click', async () => {
            const passkeyName = document.getElementById('passkey-name')?.value || '';
            button.disabled = true;

            try {
                const beginResponse = await fetch('/api/users/passkeys/register/options', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ passkey_name: passkeyName }),
                });
                const beginPayload = await beginResponse.json();

                if (!beginResponse.ok) {
                    throw new Error(window.LangelerPasskeys.message(beginPayload));
                }

                const credential = await window.LangelerPasskeys.create(beginPayload.data.passkey.options);
                const finishResponse = await fetch('/api/users/passkeys/register/verify', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        passkey_name: passkeyName,
                        credential,
                    }),
                });
                const finishPayload = await finishResponse.json();

                if (!finishResponse.ok) {
                    throw new Error(window.LangelerPasskeys.message(finishPayload));
                }

                window.location.assign(finishPayload.meta?.redirect || '/users/profile');
            } catch (error) {
                window.alert(error instanceof Error ? error.message : 'Passkey registration failed.');
            } finally {
                button.disabled = false;
            }
        });
    })();
</script>
