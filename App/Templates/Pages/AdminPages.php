<?php
declare(strict_types=1);
?>
<?php 
    $pages = is_array($pages ?? null) ? $pages : [];
    $pageForm = is_array($page_form ?? null) ? $page_form : [];
    $pageMetrics = is_array($page_metrics ?? null) ? $page_metrics : [];
 ?>

<section class="stack">
    <?= $view->renderPartial(...(array) ['PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Web pages',
        'summary' => $summary ?? '',
    ]]); ?>

    <?php if (!empty($message ?? '')): ?>
        <?= $view->renderPartial(...(array) ['StatusMessage', [
            'message' => $message,
            'status' => $status ?? 200,
        ]]); ?>
    <?php endif; ?>

    <div class="section">
        <h2>Content metrics</h2>
        <?= $view->renderComponent(...(array) ['DefinitionGrid', [
            'items' => $pageMetrics,
            'empty' => 'No page metrics are available.',
        ]]); ?>
    </div>

    <div class="section">
        <h2>Create page</h2>
        <form method="post" action="/admin/pages" class="stack">
            <label>
                Title
                <input type="text" name="title" value="<?= $view->escape((string) ($pageForm['title'] ?? '')); ?>" placeholder="About our studio" required>
            </label>

            <label>
                Slug
                <input type="text" name="slug" value="<?= $view->escape((string) ($pageForm['slug'] ?? '')); ?>" placeholder="about-our-studio">
            </label>

            <label>
                Content
                <textarea name="content" rows="8" required><?= $view->escape((string) ($pageForm['content'] ?? '')); ?></textarea>
            </label>

            <label>
                <input type="checkbox" name="is_published" value="1" <?= (!empty($pageForm['is_published'] ?? true)) ? ' checked' : '' ?>>
                Publish immediately
            </label>

            <button type="submit">Create page</button>
        </form>
    </div>

    <div class="section">
        <h2>Managed pages</h2>
        <?php if ($pages === []): ?>
            <p>No database-backed pages are available yet.</p>
        <?php else: ?>
            <?php foreach ($pages as $page): ?>
                <?php 
                    $entry = is_array($page) ? $page : [];
                    $isHome = ($entry['slug'] ?? '') === 'home';
                 ?>
                <article class="panel stack">
                    <?= $view->renderComponent(...(array) ['DefinitionGrid', [
                        'items' => [
                            'ID' => $entry['id'] ?? '',
                            'Slug' => $entry['slug'] ?? '',
                            'Status' => $entry['status'] ?? '',
                            'Updated' => $entry['updated_at'] ?? '',
                            'Public path' => $entry['view_path'] ?? '',
                        ],
                        'empty' => 'No page details are available.',
                    ]]); ?>

                    <?php if (!empty($entry['excerpt'] ?? '')): ?>
                        <p><?= $view->escape((string) ($entry['excerpt'] ?? '')); ?></p>
                    <?php endif; ?>

                    <form method="post" action="<?= $view->escape((string) ($entry['update_path'] ?? '/admin/pages')); ?>" class="stack">
                        <label>
                            Title
                            <input type="text" name="title" value="<?= $view->escape((string) ($entry['title'] ?? '')); ?>" required>
                        </label>

                        <label>
                            Slug
                            <input type="text" name="slug" value="<?= $view->escape((string) ($entry['slug'] ?? '')); ?>" required>
                        </label>

                        <label>
                            Content
                            <textarea name="content" rows="7" required><?= $view->escape((string) ($entry['content'] ?? '')); ?></textarea>
                        </label>

                        <label>
                            <input type="checkbox" name="is_published" value="1" <?= (!empty($entry['is_published'] ?? false)) ? ' checked' : '' ?>>
                            Published
                        </label>

                        <button type="submit">Save page</button>
                    </form>

                    <div class="actions">
                        <?php if (!empty($entry['is_published'] ?? false)): ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['unpublish_path'] ?? '')); ?>" onsubmit="return confirm('Unpublish this page?');">
                                <button type="submit">Unpublish</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['publish_path'] ?? '')); ?>">
                                <button type="submit">Publish</button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$isHome): ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['delete_path'] ?? '')); ?>" onsubmit="return confirm('Delete this page? This cannot be undone.');">
                                <button type="submit">Delete page</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
