<?php
declare(strict_types=1);
?>
<?php 
    $promotions = is_array($promotions ?? null) ? $promotions : [];
    $configuredPromotions = is_array($configured_promotions ?? null) ? $configured_promotions : [];
    $promotionUsage = is_array($promotion_usage ?? null) ? $promotion_usage : [];
    $promotionAnalytics = is_array($promotion_analytics ?? null) ? $promotion_analytics : [];
    $promotionForm = is_array($promotion_form ?? null) ? $promotion_form : [];
    $promotionMetrics = is_array($promotion_metrics ?? null) ? $promotion_metrics : [];
    $promotionTypes = [
        'percentage' => 'Percentage discount',
        'fixed_amount' => 'Fixed amount',
        'free_shipping' => 'Free shipping',
        'shipping_fixed' => 'Fixed shipping rate',
        'shipping_percentage' => 'Shipping percentage',
    ];
    $appliesToOptions = [
        'cart_subtotal' => 'Cart subtotal',
        'qualified_items' => 'Qualified items only',
    ];
    $analyticsColumns = [
        'key' => 'Bucket',
        'uses' => 'Uses',
        'orders' => 'Orders',
        'users' => 'Users',
        'discount_minor' => 'Discount minor',
        'item_discount_minor' => 'Item discount minor',
        'shipping_discount_minor' => 'Shipping discount minor',
    ];
    $analyticsSections = [
        'by_code' => 'By coupon code',
        'by_customer_segment' => 'By customer segment',
        'by_customer' => 'By customer',
        'by_day' => 'By day',
        'by_source' => 'By source',
        'by_currency' => 'By currency',
    ];
 ?>
<section class="stack">
    <?= $view->renderPartial(...(array) ['PageIntro', [
        'eyebrow' => 'AdminModule',
        'headline' => $headline ?? 'Promotions',
        'summary' => $summary ?? '',
    ]]); ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial(...(array) ['StatusMessage', ['message' => $message]]); ?>
    <?php endif; ?>

    <div class="section">
        <h2>Promotion metrics</h2>
        <?= $view->renderComponent(...(array) ['DefinitionGrid', [
            'items' => $promotionMetrics,
            'empty' => 'No promotion metrics are available.',
        ]]); ?>
    </div>

    <div class="section">
        <h2>Create promotion</h2>
        <form method="post" action="/admin/promotions" class="stack">
            <input type="hidden" name="active" value="0">
            <input type="hidden" name="free_shipping_eligible_only" value="0">

            <label>
                Code
                <input type="text" name="code" value="<?= $view->escape((string) ($promotionForm['code'] ?? '')); ?>" placeholder="SPRING25" required>
            </label>

            <label>
                Label
                <input type="text" name="label" value="<?= $view->escape((string) ($promotionForm['label'] ?? '')); ?>" placeholder="Spring 25%" required>
            </label>

            <label>
                Description
                <textarea name="description" rows="3"><?= $view->escape((string) ($promotionForm['description'] ?? '')); ?></textarea>
            </label>

            <label>
                Type
                <select name="type">
                    <?php foreach ($promotionTypes as $value => $label): ?>
                        <option value="<?= $view->escape($value); ?>"<?= (($promotionForm['type'] ?? 'fixed_amount') === $value) ? ' selected' : '' ?>>
                            <?= $view->escape($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Applies to
                <select name="applies_to">
                    <?php foreach ($appliesToOptions as $value => $label): ?>
                        <option value="<?= $view->escape($value); ?>"<?= (($promotionForm['applies_to'] ?? 'cart_subtotal') === $value) ? ' selected' : '' ?>>
                            <?= $view->escape($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <input type="checkbox" name="active" value="1" <?= (!empty($promotionForm['active'])) ? ' checked' : '' ?>>
                Active
            </label>

            <label>
                Rate basis points
                <input type="number" name="rate_bps" min="0" value="<?= $view->escape((int) ($promotionForm['rate_bps'] ?? 0)); ?>" placeholder="2500 = 25%">
            </label>

            <label>
                Fixed amount minor
                <input type="number" name="amount_minor" min="0" value="<?= $view->escape((int) ($promotionForm['amount_minor'] ?? 0)); ?>" placeholder="2500 = 25.00">
            </label>

            <label>
                Shipping rate minor
                <input type="number" name="shipping_rate_minor" min="0" value="<?= $view->escape((int) ($promotionForm['shipping_rate_minor'] ?? 0)); ?>" placeholder="490 = 4.90">
            </label>

            <label>
                Min subtotal minor
                <input type="number" name="min_subtotal_minor" min="0" value="<?= $view->escape((int) ($promotionForm['min_subtotal_minor'] ?? 0)); ?>">
            </label>

            <label>
                Max subtotal minor
                <input type="number" name="max_subtotal_minor" min="0" value="<?= $view->escape((int) ($promotionForm['max_subtotal_minor'] ?? 0)); ?>">
            </label>

            <label>
                Max discount minor
                <input type="number" name="max_discount_minor" min="0" value="<?= $view->escape((int) ($promotionForm['max_discount_minor'] ?? 0)); ?>">
            </label>

            <label>
                Min items
                <input type="number" name="min_items" min="0" value="<?= $view->escape((int) ($promotionForm['min_items'] ?? 0)); ?>">
            </label>

            <label>
                Max items
                <input type="number" name="max_items" min="0" value="<?= $view->escape((int) ($promotionForm['max_items'] ?? 0)); ?>">
            </label>

            <label>
                Usage limit
                <input type="number" name="usage_limit" min="0" value="<?= $view->escape((int) ($promotionForm['usage_limit'] ?? 0)); ?>" placeholder="0 = unlimited">
            </label>

            <label>
                Per-customer limit
                <input type="number" name="per_customer_limit" min="0" value="<?= $view->escape((int) ($promotionForm['criteria']['per_customer_limit'] ?? 0)); ?>" placeholder="0 = unlimited">
            </label>

            <label>
                Per-segment limit
                <input type="number" name="per_segment_limit" min="0" value="<?= $view->escape((int) ($promotionForm['criteria']['per_segment_limit'] ?? 0)); ?>" placeholder="0 = unlimited">
            </label>

            <label>
                Starts at
                <input type="text" name="starts_at" value="<?= $view->escape((string) ($promotionForm['starts_at'] ?? '')); ?>" placeholder="Optional: 2026-05-01 09:00:00">
            </label>

            <label>
                Ends at
                <input type="text" name="ends_at" value="<?= $view->escape((string) ($promotionForm['ends_at'] ?? '')); ?>" placeholder="Optional: 2026-05-31 23:59:59">
            </label>

            <label>
                Allowed currencies
                <input type="text" name="allowed_currencies" placeholder="SEK, EUR">
            </label>

            <label>
                Allowed shipping countries
                <input type="text" name="allowed_countries" placeholder="SE, NO, DK">
            </label>

            <label>
                Allowed carriers
                <input type="text" name="allowed_carriers" placeholder="postnord, instabox, budbee">
            </label>

            <label>
                Allowed shipping options
                <input type="text" name="allowed_shipping_options" placeholder="postnord-service-point, instabox-locker">
            </label>

            <label>
                Allowed fulfillment types
                <input type="text" name="allowed_fulfillment_types" placeholder="physical_shipping, digital_download, subscription">
            </label>

            <label>
                Allowed customer emails
                <input type="text" name="allowed_customer_emails" placeholder="vip@example.com, team@example.com">
            </label>

            <label>
                Allowed customer segments
                <input type="text" name="allowed_customer_segments" placeholder="customer, vip, wholesale">
            </label>

            <label>
                Required fulfillment types
                <input type="text" name="required_fulfillment_types" placeholder="digital_download, virtual_access">
            </label>

            <label>
                Required customer segments
                <input type="text" name="required_customer_segments" placeholder="customer, subscriber">
            </label>

            <label>
                Product/category criteria
                <textarea name="criteria_json" rows="5" placeholder='Optional JSON: {"allowed_product_slugs":["starter-platform-license"],"per_customer_limit":1,"allowed_customer_segments":["customer"]}'><?= $view->escape((string) ($promotionForm['criteria_json'] ?? '')); ?></textarea>
            </label>

            <label>
                <input type="checkbox" name="free_shipping_eligible_only" value="1" <?= (!empty($promotionForm['criteria']['free_shipping_eligible_only'] ?? false)) ? ' checked' : '' ?>>
                Free shipping benefit only applies to eligible delivery options
            </label>

            <div>
                <button type="submit">Create promotion</button>
            </div>
        </form>
    </div>

    <div class="section">
        <h2>Database promotions</h2>
        <?php if ($promotions === []): ?>
            <p>No database-backed promotions are available yet.</p>
        <?php else: ?>
            <form method="post" action="/admin/promotions/bulk" class="stack" onsubmit="return confirm('Apply this bulk promotion action to the selected promotions?');">
                <label>
                    Bulk lifecycle action
                    <select name="bulk_action" required>
                        <option value="">Choose action</option>
                        <option value="activate">Activate selected</option>
                        <option value="deactivate">Deactivate selected</option>
                        <option value="delete">Delete selected</option>
                    </select>
                </label>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Code</th>
                            <th>Label</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Usage</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promotions as $promotion): ?>
                            <?php $entry = is_array($promotion) ? $promotion : []; ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="promotion_ids[]" value="<?= $view->escape((string) ($entry['id'] ?? 0)); ?>">
                                </td>
                                <td><?= $view->escape((string) ($entry['code'] ?? '')); ?></td>
                                <td><?= $view->escape((string) ($entry['label'] ?? '')); ?></td>
                                <td><?= !empty($entry['active']) ? 'Active' : 'Inactive'; ?></td>
                                <td><?= $view->escape((string) ($entry['type'] ?? '')); ?></td>
                                <td><?= $view->escape((string) ($entry['usage_count'] ?? 0)); ?> / <?= $view->escape(((int) ($entry['usage_limit'] ?? 0) > 0) ? (string) $entry['usage_limit'] : 'unlimited'); ?></td>
                                <td><?= $view->escape((string) ($entry['source'] ?? 'database')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div>
                    <button type="submit">Apply bulk action</button>
                </div>
            </form>

            <?php foreach ($promotions as $promotion): ?>
                <?php 
                    $entry = is_array($promotion) ? $promotion : [];
                 ?>
                <article class="section">
                    <h3><?= $view->escape((string) ($entry['code'] ?? 'Promotion')); ?> - <?= $view->escape((string) ($entry['label'] ?? '')); ?></h3>
                    <?= $view->renderComponent(...(array) ['DefinitionGrid', [
                        'items' => [
                            'ID' => $entry['id'] ?? 0,
                            'Status' => !empty($entry['active']) ? 'Active' : 'Inactive',
                            'Type' => $entry['type'] ?? '',
                            'Applies to' => $entry['applies_to'] ?? '',
                            'Rate percent' => $entry['rate_percent'] ?? 0,
                            'Amount' => $entry['amount'] ?? '',
                            'Shipping rate' => $entry['shipping_rate'] ?? '',
                            'Max discount' => $entry['max_discount'] ?? '',
                            'Usage' => (string) ($entry['usage_count'] ?? 0) . ' / ' . ((int) ($entry['usage_limit'] ?? 0) > 0 ? (string) $entry['usage_limit'] : 'unlimited'),
                            'Starts at' => $entry['starts_at'] ?? '',
                            'Ends at' => $entry['ends_at'] ?? '',
                            'Source' => $entry['source'] ?? '',
                        ],
                    ]]); ?>

                    <form method="post" action="<?= $view->escape((string) ($entry['update_path'] ?? '/admin/promotions')); ?>" class="stack">
                        <input type="hidden" name="active" value="0">
                        <input type="hidden" name="free_shipping_eligible_only" value="0">

                        <label>
                            Code
                            <input type="text" name="code" value="<?= $view->escape((string) ($entry['code'] ?? '')); ?>" required>
                        </label>

                        <label>
                            Label
                            <input type="text" name="label" value="<?= $view->escape((string) ($entry['label'] ?? '')); ?>" required>
                        </label>

                        <label>
                            Description
                            <textarea name="description" rows="3"><?= $view->escape((string) ($entry['description'] ?? '')); ?></textarea>
                        </label>

                        <label>
                            Type
                            <select name="type">
                                <?php foreach ($promotionTypes as $value => $label): ?>
                                    <option value="<?= $view->escape($value); ?>"<?= (($entry['type'] ?? 'fixed_amount') === $value) ? ' selected' : '' ?>>
                                        <?= $view->escape($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            Applies to
                            <select name="applies_to">
                                <?php foreach ($appliesToOptions as $value => $label): ?>
                                    <option value="<?= $view->escape($value); ?>"<?= (($entry['applies_to'] ?? 'cart_subtotal') === $value) ? ' selected' : '' ?>>
                                        <?= $view->escape($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label>
                            <input type="checkbox" name="active" value="1" <?= (!empty($entry['active'])) ? ' checked' : '' ?>>
                            Active
                        </label>

                        <label>
                            Rate basis points
                            <input type="number" name="rate_bps" min="0" value="<?= $view->escape((int) ($entry['rate_bps'] ?? 0)); ?>">
                        </label>

                        <label>
                            Fixed amount minor
                            <input type="number" name="amount_minor" min="0" value="<?= $view->escape((int) ($entry['amount_minor'] ?? 0)); ?>">
                        </label>

                        <label>
                            Shipping rate minor
                            <input type="number" name="shipping_rate_minor" min="0" value="<?= $view->escape((int) ($entry['shipping_rate_minor'] ?? 0)); ?>">
                        </label>

                        <label>
                            Min subtotal minor
                            <input type="number" name="min_subtotal_minor" min="0" value="<?= $view->escape((int) ($entry['min_subtotal_minor'] ?? 0)); ?>">
                        </label>

                        <label>
                            Max subtotal minor
                            <input type="number" name="max_subtotal_minor" min="0" value="<?= $view->escape((int) ($entry['max_subtotal_minor'] ?? 0)); ?>">
                        </label>

                        <label>
                            Max discount minor
                            <input type="number" name="max_discount_minor" min="0" value="<?= $view->escape((int) ($entry['max_discount_minor'] ?? 0)); ?>">
                        </label>

                        <label>
                            Min items
                            <input type="number" name="min_items" min="0" value="<?= $view->escape((int) ($entry['min_items'] ?? 0)); ?>">
                        </label>

                        <label>
                            Max items
                            <input type="number" name="max_items" min="0" value="<?= $view->escape((int) ($entry['max_items'] ?? 0)); ?>">
                        </label>

                        <label>
                            Usage limit
                            <input type="number" name="usage_limit" min="0" value="<?= $view->escape((int) ($entry['usage_limit'] ?? 0)); ?>">
                        </label>

                        <label>
                            Per-customer limit
                            <input type="number" name="per_customer_limit" min="0" value="<?= $view->escape((int) ($entry['criteria']['per_customer_limit'] ?? 0)); ?>">
                        </label>

                        <label>
                            Per-segment limit
                            <input type="number" name="per_segment_limit" min="0" value="<?= $view->escape((int) ($entry['criteria']['per_segment_limit'] ?? 0)); ?>">
                        </label>

                        <label>
                            Starts at
                            <input type="text" name="starts_at" value="<?= $view->escape((string) ($entry['starts_at'] ?? '')); ?>">
                        </label>

                        <label>
                            Ends at
                            <input type="text" name="ends_at" value="<?= $view->escape((string) ($entry['ends_at'] ?? '')); ?>">
                        </label>

                        <label>
                            Criteria JSON
                            <textarea name="criteria_json" rows="5"><?= $view->escape((string) ($entry['criteria_input'] ?? '')); ?></textarea>
                        </label>

                        <label>
                            <input type="checkbox" name="free_shipping_eligible_only" value="1" <?= (!empty($entry['criteria']['free_shipping_eligible_only'] ?? false)) ? ' checked' : '' ?>>
                            Free shipping benefit only applies to eligible delivery options
                        </label>

                        <div>
                            <button type="submit">Save promotion</button>
                        </div>
                    </form>

                    <div>
                        <?php if (!empty($entry['active'])): ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['deactivate_path'] ?? '')); ?>">
                                <button type="submit">Deactivate promotion</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="<?= $view->escape((string) ($entry['activate_path'] ?? '')); ?>">
                                <button type="submit">Activate promotion</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="<?= $view->escape((string) ($entry['delete_path'] ?? '')); ?>" onsubmit="return confirm('Delete this promotion? This cannot be undone.');">
                            <button type="submit">Delete promotion</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Promotion analytics</h2>
        <?php foreach ($analyticsSections as $key => $label): ?>
            <article class="section">
                <h3><?= $view->escape($label); ?></h3>
                <?= $view->renderComponent(...(array) ['DataTable', [
                    'columns' => $analyticsColumns,
                    'rows' => is_array($promotionAnalytics[$key] ?? null) ? $promotionAnalytics[$key] : [],
                    'empty' => 'No analytics rows are available for this segment yet.',
                ]]); ?>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="section">
        <h2>Recent usage</h2>
        <?= $view->renderComponent(...(array) ['DataTable', [
            'columns' => [
                'promotion_code' => 'Code',
                'source' => 'Source',
                'order_id' => 'Order',
                'user_id' => 'User',
                'currency' => 'Currency',
                'discount_minor' => 'Discount minor',
                'shipping_discount_minor' => 'Shipping discount minor',
                'created_at' => 'Used at',
                'order_path' => 'Order path',
            ],
            'rows' => $promotionUsage,
            'empty' => 'No promotion usage has been recorded yet.',
        ]]); ?>
    </div>

    <div class="section">
        <h2>Configured baseline promotions</h2>
        <?= $view->renderComponent(...(array) ['DataTable', [
            'columns' => [
                'code' => 'Code',
                'label' => 'Label',
                'type' => 'Type',
                'active' => 'Active',
                'amount' => 'Amount',
                'source' => 'Source',
            ],
            'rows' => $configuredPromotions,
            'empty' => 'No config-backed promotions are currently configured.',
        ]]); ?>
    </div>
</section>
