<?php

$entitlement = is_array($entitlement ?? null) ? $entitlement : [];
$metadata = is_array($entitlement['metadata'] ?? null) ? $entitlement['metadata'] : [];
$limit = (int) ($entitlement['download_limit'] ?? 0);
$used = (int) ($entitlement['downloads_used'] ?? 0);
?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'OrderModule',
        'headline' => $headline ?? 'Purchased access',
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <?php if ($entitlement === []): ?>
        <div class="section">
            <h2>Access unavailable</h2>
            <p>The requested purchased access token could not be resolved.</p>
            <p><a href="/orders">Return to orders</a></p>
        </div>
    <?php else: ?>
        <div class="section">
            <h2>Access details</h2>
            <?= $view->renderComponent('DefinitionGrid', [
                'items' => [
                    'Label' => $entitlement['label'] ?? '',
                    'Type' => $entitlement['type'] ?? '',
                    'Status' => $entitlement['status'] ?? '',
                    'Downloads' => $limit > 0 ? $used . ' / ' . $limit : 'Unlimited',
                    'Starts at' => $entitlement['starts_at'] ?? '',
                    'Expires at' => $entitlement['expires_at'] ?? '',
                    'Last accessed' => $entitlement['last_accessed_at'] ?? '',
                ],
            ]) ?>

            <?php if (!empty($entitlement['access_url'])): ?>
                <p><a href="<?= $view->escape((string) $entitlement['access_url']) ?>">Open purchased content</a></p>
            <?php else: ?>
                <p>This entitlement is active. Attach a download URL or content URL in the product fulfillment policy to expose a direct content link.</p>
            <?php endif; ?>
        </div>

        <?php if ($metadata !== []): ?>
            <div class="section">
                <h2>Access metadata</h2>
                <?= $view->renderComponent('DefinitionGrid', [
                    'items' => array_map(
                        static fn(mixed $value): string => is_scalar($value) || $value === null
                            ? (string) $value
                            : (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        $metadata
                    ),
                ]) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
