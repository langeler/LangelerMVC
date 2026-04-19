<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'UserModule',
        'headline' => $headline ?? 'Profile',
        'summary' => $summary ?? '',
    ]) ?>

    <div class="section">
        <h2>Account overview</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => is_array($user ?? null) ? [
                'Name' => $user['name'] ?? '',
                'Email' => $user['email'] ?? '',
                'Status' => $user['status'] ?? '',
                'Email Verified' => !empty($user['emailVerified']) ? 'Yes' : 'No',
                'OTP Enabled' => !empty($user['otpEnabled']) ? 'Yes' : 'No',
            ] : [],
            'empty' => 'No profile data is available.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Update profile</h2>
        <form method="post" action="/users/profile">
            <label>Name<br><input type="text" name="name" value="<?= $view->escape((string) ($user['name'] ?? '')) ?>" required></label>
            <label>Email<br><input type="email" name="email" value="<?= $view->escape((string) ($user['email'] ?? '')) ?>" required></label>
            <button type="submit">Save profile</button>
        </form>
    </div>

    <div class="section">
        <h2>Change password</h2>
        <form method="post" action="/users/password/change">
            <label>Current Password<br><input type="password" name="current_password" required></label>
            <label>New Password<br><input type="password" name="password" required></label>
            <label>Confirm New Password<br><input type="password" name="password_confirmation" required></label>
            <button type="submit">Change password</button>
        </form>
    </div>

    <div class="section">
        <h2>Roles</h2>
        <?= $view->renderComponent('BadgeList', [
            'items' => is_array($roles ?? null) ? $roles : [],
            'empty' => 'No roles assigned.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Permissions</h2>
        <?= $view->renderComponent('BadgeList', [
            'items' => is_array($permissions ?? null) ? $permissions : [],
            'empty' => 'No permissions assigned.',
        ]) ?>
    </div>

    <div class="section">
        <h2>OTP</h2>
        <form method="post" action="/users/otp/enable">
            <button type="submit">Provision OTP</button>
        </form>
        <form method="post" action="/users/otp/verify">
            <label>Current OTP Code<br><input type="text" name="otp_code" inputmode="numeric"></label>
            <label><input type="checkbox" name="trust_device" value="1"> Trust this device after verification</label>
            <button type="submit">Verify OTP</button>
        </form>
        <form method="post" action="/users/otp/recovery-codes/regenerate">
            <button type="submit">Regenerate recovery codes</button>
        </form>
        <form method="post" action="/users/otp/trusted-devices/revoke">
            <button type="submit">Revoke trusted devices</button>
        </form>
        <form method="post" action="/users/otp/disable">
            <button type="submit">Disable OTP</button>
        </form>
        <?php if (!empty($recoveryCodes ?? [])): ?>
            <h3>Recovery codes</h3>
            <?= $view->renderComponent('CodeList', ['items' => $recoveryCodes]) ?>
        <?php endif; ?>
        <h3>Trusted devices</h3>
        <?php if (!empty($trustedDevices ?? [])): ?>
            <div class="stack">
                <?php foreach (($trustedDevices ?? []) as $device): ?>
                    <article class="section">
                        <?= $view->renderComponent('DefinitionGrid', [
                            'items' => [
                                'Device' => $device['payload']['label'] ?? 'Trusted browser',
                                'Trusted Until' => $device['payload']['trusted_until'] ?? ($device['expires_at'] ?? ''),
                                'Created At' => $device['created_at'] ?? '',
                            ],
                        ]) ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No trusted OTP devices are currently stored.</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Passkeys</h2>
        <p>
            <?php if (!empty($passkeySupport['available'])): ?>
                Driver: <?= $view->escape((string) ($passkeySupport['driver'] ?? 'unknown')) ?>
            <?php else: ?>
                Passkeys are not available in the current runtime.
            <?php endif; ?>
        </p>
        <?php if (!empty($passkeys ?? [])): ?>
            <div class="stack">
                <?php foreach (($passkeys ?? []) as $passkey): ?>
                    <article class="section">
                        <strong><?= $view->escape((string) ($passkey['name'] ?? 'Passkey')) ?></strong>
                        <?= $view->renderComponent('DefinitionGrid', [
                            'items' => [
                                'Counter' => $passkey['counter'] ?? 0,
                                'Last used' => $passkey['lastUsedAt'] ?? 'Not used yet',
                            ],
                        ]) ?>
                        <form method="post" action="/users/passkeys/<?= (int) ($passkey['id'] ?? 0) ?>/delete">
                            <button type="submit">Remove passkey</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No passkeys are registered for this account yet.</p>
        <?php endif; ?>
        <div class="stack">
            <label>Passkey Name<br><input type="text" id="passkey-name" name="passkey_name" placeholder="My phone passkey"></label>
            <button type="button" id="register-passkey-button">Register passkey</button>
        </div>
    </div>

    <div class="section">
        <h2>Account actions</h2>
        <?= $view->renderComponent('LinkList', [
            'links' => [
                ['href' => '/users/login', 'label' => 'Sign in'],
                ['href' => '/users/register', 'label' => 'Register another account'],
            ],
        ]) ?>
        <form method="post" action="/users/email/verify">
            <button type="submit">Resend verification email</button>
        </form>
        <form method="post" action="/users/logout">
            <button type="submit">Sign out</button>
        </form>
    </div>
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
