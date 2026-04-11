<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $view->escape((string) ($title ?? $appName ?? 'LangelerMVC')) ?></title>
    <meta name="description" content="<?= $view->escape((string) ($metaDescription ?? $summary ?? '')) ?>">
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
        a { color: var(--accent); }
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
        .meta,
        .intro__eyebrow {
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
        .intro {
            display: grid;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .intro__eyebrow {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.78rem;
        }
        .intro__headline {
            margin: 0;
            font-size: clamp(2rem, 5vw, 3.5rem);
            line-height: 1.05;
        }
        .intro__summary {
            margin: 0;
            max-width: 42rem;
            font-size: 1.1rem;
            line-height: 1.7;
            color: #3f3b34;
        }
        .body-copy {
            margin: 1.5rem 0 0;
            max-width: 46rem;
            font-size: 1rem;
            line-height: 1.9;
            color: #2b2822;
        }
        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 2rem;
        }
        .cta-row a,
        .badge-list li {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.85rem 1.15rem;
            text-decoration: none;
        }
        .cta-row a {
            background: var(--accent);
            color: #fff;
        }
        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .badge-list li {
            background: var(--accent-soft);
            color: var(--accent);
        }
        .message {
            margin: 1rem 0 1.5rem;
            padding: 1rem 1.2rem;
            border-radius: 1rem;
            border: 1px solid rgba(15, 107, 91, 0.2);
            background: var(--accent-soft);
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
</head>
<body>
    <div class="shell">
        <header class="shell__header">
            <div class="brand">
                <strong><?= $view->escape((string) ($appName ?? 'LangelerMVC')) ?></strong>
                <span><?= $view->escape((string) ($moduleName ?? 'WebModule')) ?> presentation surface</span>
            </div>
            <span>Version <?= $view->escape((string) ($appVersion ?? '1.0.0')) ?></span>
        </header>

        <main class="panel">
            <?= $content ?? '' ?>

            <?= $view->renderPartial('PanelMeta', [
                'items' => [
                    'Status' => $meta['status'] ?? $status ?? 200,
                    'Source' => $meta['source'] ?? $source ?? 'memory',
                    'Generated' => $meta['generatedAt'] ?? '',
                ],
            ]) ?>
        </main>

        <footer class="shell__footer">
            <span>Web pages now render through reusable templates, shared partials, and module-aware layouts.</span>
        </footer>
    </div>
</body>
</html>
