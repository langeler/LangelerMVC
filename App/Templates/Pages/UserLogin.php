<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'Sign in'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (($message ?? '') !== ''): ?>
        <p><strong><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>
    <form method="post" action="/users/login">
        <p><label>Email<br><input type="email" name="email" required></label></p>
        <p><label>Password<br><input type="password" name="password" required></label></p>
        <p><label>OTP Code (if enabled)<br><input type="text" name="otp_code" inputmode="numeric"></label></p>
        <p><label>Recovery Code (if needed)<br><input type="text" name="recovery_code" autocapitalize="characters"></label></p>
        <p><label><input type="checkbox" name="remember" value="1"> Remember me</label></p>
        <p><button type="submit">Sign in</button></p>
    </form>
    <p><button type="button" id="passkey-login-button">Sign in with passkey</button></p>
    <p><a href="/users/password/forgot">Forgot password?</a></p>
</section>
<script>
    (() => {
        const button = document.getElementById('passkey-login-button');

        if (!button) {
            return;
        }

        if (!window.LangelerPasskeys || !window.LangelerPasskeys.isSupported()) {
            button.disabled = true;
            button.textContent = 'Passkeys unavailable';
            return;
        }

        button.addEventListener('click', async () => {
            const email = document.querySelector('input[name="email"]')?.value || '';
            const remember = Boolean(document.querySelector('input[name="remember"]')?.checked);
            button.disabled = true;

            try {
                const beginResponse = await fetch('/api/users/passkeys/login/options', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, remember }),
                });
                const beginPayload = await beginResponse.json();

                if (!beginResponse.ok) {
                    throw new Error(window.LangelerPasskeys.message(beginPayload));
                }

                const credential = await window.LangelerPasskeys.get(beginPayload.data.passkey.options);
                const finishResponse = await fetch('/api/users/passkeys/login/verify', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ credential }),
                });
                const finishPayload = await finishResponse.json();

                if (!finishResponse.ok) {
                    throw new Error(window.LangelerPasskeys.message(finishPayload));
                }

                window.location.assign(finishPayload.meta?.redirect || '/users/profile');
            } catch (error) {
                window.alert(error instanceof Error ? error.message : 'Passkey sign in failed.');
            } finally {
                button.disabled = false;
            }
        });
    })();
</script>
