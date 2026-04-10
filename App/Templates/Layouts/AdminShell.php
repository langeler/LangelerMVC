<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($title ?? $appName ?? 'LangelerMVC Admin'), ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars((string) ($metaDescription ?? $summary ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <style>
        :root {
            color-scheme: light;
            --bg: #f2f4f6;
            --panel: #ffffff;
            --ink: #162027;
            --muted: #5d6973;
            --line: #d5dce2;
            --accent: #1d5f8a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Avenir Next", "Segoe UI", sans-serif;
            background:
                linear-gradient(180deg, rgba(29, 95, 138, 0.08), transparent 18rem),
                var(--bg);
            color: var(--ink);
        }
        .shell {
            width: min(1140px, calc(100% - 2rem));
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
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--line);
        }
        .panel {
            background: var(--panel);
            border: 1px solid rgba(213, 220, 226, 0.85);
            border-radius: 1.2rem;
            padding: 2rem;
            box-shadow: 0 1rem 2.5rem rgba(22, 32, 39, 0.08);
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 0.94rem;
        }
        a { color: var(--accent); }
    </style>
</head>
<body>
    <div class="shell">
        <header class="header">
            <strong><?= htmlspecialchars((string) ($appName ?? 'LangelerMVC'), ENT_QUOTES, 'UTF-8') ?> Admin</strong>
            <span><?= htmlspecialchars((string) ($headline ?? 'Operations'), ENT_QUOTES, 'UTF-8') ?></span>
        </header>
        <main class="panel">
            <?= $content ?? '' ?>
            <div class="meta">
                <span>Status: <?= htmlspecialchars((string) ($meta['status'] ?? $status ?? 200), ENT_QUOTES, 'UTF-8') ?></span>
                <span>Module: <?= htmlspecialchars((string) ($meta['module'] ?? 'AdminModule'), ENT_QUOTES, 'UTF-8') ?></span>
                <span>Generated: <?= htmlspecialchars((string) ($meta['generatedAt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </main>
        <footer class="footer">
            <span>RBAC-backed management surfaces now share the same service and response pipeline as the rest of the framework.</span>
        </footer>
    </div>
</body>
</html>
