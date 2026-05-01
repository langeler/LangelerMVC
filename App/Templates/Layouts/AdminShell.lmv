<!DOCTYPE html>
<html lang="en" data-theme="<?= $view->escape((string) ($themeName ?? 'bootstrap-light')) ?>" data-theme-mode="<?= $view->escape((string) ($themeMode ?? 'system')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $view->escape((string) ($title ?? $appName ?? 'LangelerMVC Admin')) ?></title>
    <meta name="description" content="<?= $view->escape((string) ($metaDescription ?? $summary ?? '')) ?>">
    <style>
        :root {
            color-scheme: light;
            --bg: #f2f4f6;
            --panel: #ffffff;
            --ink: #162027;
            --muted: #5d6973;
            --line: #d5dce2;
            --accent: #1d5f8a;
            --accent-soft: #e4f0f8;
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
        a { color: var(--accent); }
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
        .intro {
            display: grid;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
        }
        .intro__eyebrow,
        .meta,
        .footer {
            color: var(--muted);
            font-size: 0.94rem;
        }
        .intro__eyebrow {
            margin: 0;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.78rem;
        }
        .intro__headline {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.6rem);
            line-height: 1.1;
        }
        .intro__summary {
            margin: 0;
            max-width: 48rem;
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
        .badge-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .badge-list li {
            padding: 0.55rem 0.85rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
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
        .link-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }
        .system-dump {
            margin: 0;
            padding: 1rem;
            border-radius: 1rem;
            background: #f7fafc;
            border: 1px solid var(--line);
            overflow: auto;
        }
    </style>
    <link rel="stylesheet" href="<?= $view->escape((string) ($themeAssetCss ?? '/assets/css/langelermvc-theme.css')) ?>">
</head>
<body class="theme-surface theme-surface--<?= $view->escape((string) ($themeSurface ?? 'admin')) ?> <?= $view->escape((string) ($themeClass ?? 'theme-bootstrap-light')) ?>"
      data-theme="<?= $view->escape((string) ($themeName ?? 'bootstrap-light')) ?>"
      data-theme-mode="<?= $view->escape((string) ($themeMode ?? 'system')) ?>"
      data-theme-default-mode="<?= $view->escape((string) ($themeDefaultMode ?? 'system')) ?>"
      data-theme-storage-key="<?= $view->escape((string) ($themeStorageKey ?? 'langelermvc.theme')) ?>">
    <div class="shell">
        <header class="header">
            <strong><?= $view->escape((string) ($appName ?? 'LangelerMVC')) ?> Admin</strong>
            <div class="theme-header-actions">
                <span><?= $view->escape((string) ($headline ?? 'Operations')) ?></span>
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
                    'Module' => $meta['module'] ?? 'AdminModule',
                    'Generated' => $meta['generatedAt'] ?? '',
                ],
            ]) ?>
        </main>
        <footer class="footer">
            <span>Administrative surfaces now reuse the same presentation contracts as the rest of the framework.</span>
        </footer>
    </div>
    <script src="<?= $view->escape((string) ($themeAssetJs ?? '/assets/js/langelermvc-theme.js')) ?>" defer></script>
</body>
</html>
