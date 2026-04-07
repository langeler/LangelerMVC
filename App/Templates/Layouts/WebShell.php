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
            --bg: #f6f4ef;
            --panel: #fffdf8;
            --ink: #1b1a17;
            --muted: #6a665d;
            --line: #d8d0c2;
            --accent: #0f6b5b;
            --accent-soft: #dff3ee;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top right, rgba(15, 107, 91, 0.10), transparent 28rem),
                linear-gradient(180deg, #faf8f2 0%, var(--bg) 100%);
            color: var(--ink);
        }
        .shell {
            width: min(960px, calc(100% - 2rem));
            margin: 0 auto;
            padding: 2rem 0 4rem;
        }
        .shell__header,
        .shell__footer {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
        }
        .shell__header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--line);
        }
        .brand {
            display: grid;
            gap: 0.2rem;
        }
        .brand strong {
            font-size: 1.1rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .brand span,
        .shell__footer,
        .meta {
            color: var(--muted);
            font-size: 0.95rem;
        }
        .panel {
            background: var(--panel);
            border: 1px solid rgba(216, 208, 194, 0.75);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 1.5rem 4rem rgba(27, 26, 23, 0.06);
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }
        a {
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="shell__header">
            <div class="brand">
                <strong><?= htmlspecialchars((string) ($appName ?? 'LangelerMVC'), ENT_QUOTES, 'UTF-8') ?></strong>
                <span>Structured PHP MVC foundation</span>
            </div>
            <span>Version <?= htmlspecialchars((string) ($appVersion ?? '1.0.0'), ENT_QUOTES, 'UTF-8') ?></span>
        </header>

        <main class="panel">
            <?= $content ?? '' ?>

            <div class="meta">
                <span>Status: <?= htmlspecialchars((string) ($meta['status'] ?? $status ?? 200), ENT_QUOTES, 'UTF-8') ?></span>
                <span>Source: <?= htmlspecialchars((string) ($meta['source'] ?? $source ?? 'memory'), ENT_QUOTES, 'UTF-8') ?></span>
                <span>Generated: <?= htmlspecialchars((string) ($meta['generatedAt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </main>

        <footer class="shell__footer">
            <span>WebModule now runs through request, service, presenter, view, and response layers.</span>
        </footer>
    </div>
</body>
</html>
