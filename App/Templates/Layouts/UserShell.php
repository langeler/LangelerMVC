<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($title ?? $appName ?? 'LangelerMVC'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars((string) ($metaDescription ?? $summary ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <style>
        :root {
            color-scheme: light;
            --bg: #fbf7f0;
            --panel: #fffdf8;
            --ink: #221f1a;
            --muted: #766f64;
            --line: #ddd2c2;
            --accent: #b44c2f;
            --accent-soft: #f7e3dd;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Iowan Old Style", "Palatino Linotype", serif;
            background:
                radial-gradient(circle at top left, rgba(180, 76, 47, 0.10), transparent 24rem),
                linear-gradient(180deg, #fff9f4 0%, var(--bg) 100%);
            color: var(--ink);
        }
        .shell {
            width: min(1040px, calc(100% - 2rem));
            margin: 0 auto;
            padding: 2rem 0 4rem;
        }
        .header, .footer {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
        }
        .header {
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--line);
        }
        .brand strong {
            display: block;
            font-size: 1.15rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .brand span, .footer, .meta {
            color: var(--muted);
            font-size: 0.95rem;
        }
        .panel {
            background: var(--panel);
            border: 1px solid rgba(221, 210, 194, 0.8);
            border-radius: 1.4rem;
            padding: 2rem;
            box-shadow: 0 1.25rem 3rem rgba(34, 31, 26, 0.07);
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }
        a { color: var(--accent); }
    </style>
    <script>
        window.LangelerPasskeys = {
            isSupported() {
                return typeof window !== 'undefined'
                    && typeof window.PublicKeyCredential !== 'undefined'
                    && typeof navigator !== 'undefined'
                    && navigator.credentials
                    && typeof navigator.credentials.create === 'function'
                    && typeof navigator.credentials.get === 'function';
            },
            base64urlToBuffer(value) {
                const normalized = String(value).replace(/-/g, '+').replace(/_/g, '/');
                const padded = normalized + '='.repeat((4 - normalized.length % 4) % 4);
                const binary = window.atob(padded);
                const bytes = new Uint8Array(binary.length);

                for (let index = 0; index < binary.length; index += 1) {
                    bytes[index] = binary.charCodeAt(index);
                }

                return bytes.buffer;
            },
            bufferToBase64url(buffer) {
                const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
                let binary = '';

                bytes.forEach((byte) => {
                    binary += String.fromCharCode(byte);
                });

                return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
            },
            creationOptionsFromJSON(options) {
                if (typeof window.PublicKeyCredential.parseCreationOptionsFromJSON === 'function') {
                    return window.PublicKeyCredential.parseCreationOptionsFromJSON(options);
                }

                const copy = JSON.parse(JSON.stringify(options));
                copy.challenge = this.base64urlToBuffer(copy.challenge);
                copy.user.id = this.base64urlToBuffer(copy.user.id);
                copy.excludeCredentials = (copy.excludeCredentials || []).map((credential) => ({
                    ...credential,
                    id: this.base64urlToBuffer(credential.id),
                }));

                return copy;
            },
            requestOptionsFromJSON(options) {
                if (typeof window.PublicKeyCredential.parseRequestOptionsFromJSON === 'function') {
                    return window.PublicKeyCredential.parseRequestOptionsFromJSON(options);
                }

                const copy = JSON.parse(JSON.stringify(options));
                copy.challenge = this.base64urlToBuffer(copy.challenge);
                copy.allowCredentials = (copy.allowCredentials || []).map((credential) => ({
                    ...credential,
                    id: this.base64urlToBuffer(credential.id),
                }));

                return copy;
            },
            credentialToJSON(credential) {
                if (credential && typeof credential.toJSON === 'function') {
                    return credential.toJSON();
                }

                const response = credential.response || {};
                const payload = {
                    id: credential.id,
                    rawId: this.bufferToBase64url(credential.rawId),
                    type: credential.type,
                    response: {
                        clientDataJSON: this.bufferToBase64url(response.clientDataJSON),
                    },
                };

                if (response.attestationObject) {
                    payload.response.attestationObject = this.bufferToBase64url(response.attestationObject);
                }

                if (typeof response.getTransports === 'function') {
                    payload.response.transports = response.getTransports();
                }

                if (response.authenticatorData) {
                    payload.response.authenticatorData = this.bufferToBase64url(response.authenticatorData);
                }

                if (response.signature) {
                    payload.response.signature = this.bufferToBase64url(response.signature);
                }

                if (response.userHandle) {
                    payload.response.userHandle = this.bufferToBase64url(response.userHandle);
                }

                return payload;
            },
            async create(options) {
                const credential = await navigator.credentials.create({
                    publicKey: this.creationOptionsFromJSON(options),
                });

                return this.credentialToJSON(credential);
            },
            async get(options) {
                const credential = await navigator.credentials.get({
                    publicKey: this.requestOptionsFromJSON(options),
                });

                return this.credentialToJSON(credential);
            },
            message(payload) {
                return payload?.data?.message
                    || payload?.data?.title
                    || payload?.message
                    || 'The requested authentication action could not be completed.';
            },
        };
    </script>
</head>
<body>
    <div class="shell">
        <header class="header">
            <div class="brand">
                <strong><?= htmlspecialchars((string) ($appName ?? 'LangelerMVC'), ENT_QUOTES, 'UTF-8') ?></strong>
                <span>User and identity platform surface</span>
            </div>
            <span>Version <?= htmlspecialchars((string) ($appVersion ?? '1.0.0'), ENT_QUOTES, 'UTF-8') ?></span>
        </header>
        <main class="panel">
            <?= $content ?? '' ?>
            <div class="meta">
                <span>Status: <?= htmlspecialchars((string) ($meta['status'] ?? $status ?? 200), ENT_QUOTES, 'UTF-8') ?></span>
                <span>Module: <?= htmlspecialchars((string) ($meta['module'] ?? 'UserModule'), ENT_QUOTES, 'UTF-8') ?></span>
                <span>Generated: <?= htmlspecialchars((string) ($meta['generatedAt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </main>
        <footer class="footer">
            <span>Session auth, verification, password reset, OTP, and passkeys are now part of the framework lifecycle.</span>
        </footer>
    </div>
</body>
</html>
