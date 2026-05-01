<!DOCTYPE html>
<html lang="en" data-theme="<?= $view->escape((string) ($themeName ?? 'bootstrap-light')) ?>" data-theme-mode="<?= $view->escape((string) ($themeMode ?? 'system')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $view->escape((string) ($title ?? $appName ?? 'LangelerMVC')) ?></title>
    <meta name="description" content="<?= $view->escape((string) ($metaDescription ?? $summary ?? '')) ?>">
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
        a { color: var(--accent); }
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
        .brand span, .footer, .meta, .intro__eyebrow {
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
        .intro {
            display: grid;
            gap: 0.65rem;
            margin-bottom: 1.5rem;
        }
        .intro__eyebrow {
            margin: 0;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.78rem;
        }
        .intro__headline {
            margin: 0;
            font-size: clamp(1.9rem, 4vw, 3rem);
            line-height: 1.1;
        }
        .intro__summary {
            margin: 0;
            max-width: 42rem;
            line-height: 1.7;
        }
        .stack {
            display: grid;
            gap: 1.25rem;
        }
        .section {
            display: grid;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }
        .section h2,
        .section h3 {
            margin: 0;
        }
        .message {
            margin: 1rem 0 1.5rem;
            padding: 1rem 1.2rem;
            border-radius: 1rem;
            border: 1px solid rgba(180, 76, 47, 0.2);
            background: var(--accent-soft);
        }
        .definition-grid {
            display: grid;
            grid-template-columns: max-content 1fr;
            gap: 0.5rem 1rem;
            margin: 0;
        }
        .definition-grid dt {
            font-weight: 700;
        }
        .definition-grid dd {
            margin: 0;
        }
        .badge-list,
        .code-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .badge-list li,
        .code-list li {
            padding: 0.55rem 0.85rem;
            border-radius: 999px;
            background: var(--accent-soft);
        }
        .link-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
        }
        form {
            display: grid;
            gap: 0.75rem;
            max-width: 32rem;
        }
        input,
        button {
            font: inherit;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.7rem 0.8rem;
            border-radius: 0.8rem;
            border: 1px solid var(--line);
            background: #fff;
        }
        button {
            width: fit-content;
            padding: 0.75rem 1rem;
            border-radius: 999px;
            border: 0;
            background: var(--accent);
            color: #fff;
            cursor: pointer;
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }
    </style>
    <link rel="stylesheet" href="<?= $view->escape((string) ($themeAssetCss ?? '/assets/css/langelermvc-theme.css')) ?>">
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
<body class="theme-surface theme-surface--<?= $view->escape((string) ($themeSurface ?? 'auth')) ?> <?= $view->escape((string) ($themeClass ?? 'theme-bootstrap-light')) ?>"
      data-theme="<?= $view->escape((string) ($themeName ?? 'bootstrap-light')) ?>"
      data-theme-mode="<?= $view->escape((string) ($themeMode ?? 'system')) ?>"
      data-theme-default-mode="<?= $view->escape((string) ($themeDefaultMode ?? 'system')) ?>"
      data-theme-storage-key="<?= $view->escape((string) ($themeStorageKey ?? 'langelermvc.theme')) ?>">
    <div class="shell">
        <header class="header">
            <div class="brand">
                <strong><?= $view->escape((string) ($appName ?? 'LangelerMVC')) ?></strong>
                <span><?= $view->escape((string) ($moduleName ?? 'UserModule')) ?> identity platform</span>
            </div>
            <div class="theme-header-actions">
                <span>Version <?= $view->escape((string) ($appVersion ?? '1.0.0')) ?></span>
                <?php if ($themeToggleEnabled ?? true): ?>
                    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle color theme" aria-pressed="false">
                        <span data-theme-toggle-label><?= $view->escape(ucfirst((string) ($themeMode ?? 'system'))) ?></span>
                    </button>
                <?php endif; ?>
            </div>
        </header>
        <main class="panel">
            <?= $content ?? '' ?>
            <?= $view->renderPartial('PanelMeta', [
                'items' => [
                    'Status' => $meta['status'] ?? $status ?? 200,
                    'Module' => $meta['module'] ?? 'UserModule',
                    'Generated' => $meta['generatedAt'] ?? '',
                ],
            ]) ?>
        </main>
        <footer class="footer">
            <span>Identity flows, OTP, passkeys, and JSON resources now share one presentation pipeline.</span>
        </footer>
    </div>
    <script src="<?= $view->escape((string) ($themeAssetJs ?? '/assets/js/langelermvc-theme.js')) ?>" defer></script>
</body>
</html>
