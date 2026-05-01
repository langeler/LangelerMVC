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
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }
        .product-card {
            display: grid;
            gap: 0.85rem;
            padding: 1rem;
            border: 1px solid var(--line);
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, 0.72);
        }
        .product-card__media,
        .product-hero {
            overflow: hidden;
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(15, 107, 91, 0.08), rgba(15, 107, 91, 0.02));
            border: 1px solid rgba(15, 107, 91, 0.12);
        }
        .product-card__media img,
        .product-hero img {
            display: block;
            width: 100%;
            height: auto;
        }
        .product-card__body {
            display: grid;
            gap: 0.6rem;
        }
        .product-card__eyebrow {
            margin: 0;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 0.78rem;
        }
        .product-card__title {
            margin: 0;
            font-size: 1.2rem;
        }
        .product-card__summary {
            margin: 0;
            color: #3f3b34;
            line-height: 1.65;
        }
        .product-card__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            color: var(--muted);
            font-size: 0.95rem;
        }
        .product-card__link {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 999px;
            text-decoration: none;
            background: var(--accent);
            color: #fff;
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
        @media (max-width: 720px) {
            .shell__header,
            .shell__footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    <link rel="stylesheet" href="<?= $view->escape((string) ($themeAssetCss ?? '/assets/css/langelermvc-theme.css')) ?>">
</head>
<body class="theme-surface theme-surface--<?= $view->escape((string) ($themeSurface ?? 'web')) ?> <?= $view->escape((string) ($themeClass ?? 'theme-bootstrap-light')) ?>"
      data-theme="<?= $view->escape((string) ($themeName ?? 'bootstrap-light')) ?>"
      data-theme-mode="<?= $view->escape((string) ($themeMode ?? 'system')) ?>"
      data-theme-default-mode="<?= $view->escape((string) ($themeDefaultMode ?? 'system')) ?>"
      data-theme-storage-key="<?= $view->escape((string) ($themeStorageKey ?? 'langelermvc.theme')) ?>">
    <div class="shell">
        <header class="shell__header">
            <div class="brand">
                <strong><?= $view->escape((string) ($appName ?? 'LangelerMVC')) ?></strong>
                <span><?= $view->escape((string) ($moduleName ?? 'WebModule')) ?> presentation surface</span>
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
                    'Source' => $meta['source'] ?? $source ?? 'memory',
                    'Generated' => $meta['generatedAt'] ?? '',
                ],
            ]) ?>
        </main>

        <footer class="shell__footer">
            <span>Web pages now render through reusable templates, shared partials, and module-aware layouts.</span>
        </footer>
    </div>
    <script src="<?= $view->escape((string) ($themeAssetJs ?? '/assets/js/langelermvc-theme.js')) ?>" defer></script>
</body>
</html>
