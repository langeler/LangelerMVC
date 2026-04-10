<section>
    <h1><?= htmlspecialchars((string) ($headline ?? 'System'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <h2>Modules</h2>
    <p><?= htmlspecialchars(implode(', ', $modules ?? []), ENT_QUOTES, 'UTF-8') ?></p>
    <h2>System</h2>
    <pre><?= htmlspecialchars(json_encode($system ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
