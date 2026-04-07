<section class="<?= htmlspecialchars((string) ($pageClass ?? 'page-not-found'), ENT_QUOTES, 'UTF-8') ?>">
    <p style="margin:0 0 0.75rem;color:#6a665d;text-transform:uppercase;letter-spacing:0.08em;font-size:0.78rem;">Fallback</p>
    <h1 style="margin:0;font-size:clamp(2rem, 5vw, 3.25rem);line-height:1.05;"><?= htmlspecialchars((string) ($headline ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
    <p style="margin:1rem 0 0;max-width:40rem;font-size:1.05rem;line-height:1.7;color:#3f3b34;"><?= htmlspecialchars((string) ($summary ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <p style="margin:1.5rem 0 0;max-width:44rem;font-size:1rem;line-height:1.9;color:#2b2822;"><?= htmlspecialchars((string) ($body ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <div style="margin-top:2rem;">
        <a href="<?= htmlspecialchars((string) (($callToAction['href'] ?? '/')), ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;padding:0.9rem 1.2rem;border-radius:999px;background:#0f6b5b;color:#fff;text-decoration:none;">
            <?= htmlspecialchars((string) (($callToAction['label'] ?? 'Return Home')), ENT_QUOTES, 'UTF-8') ?>
        </a>
    </div>
</section>
