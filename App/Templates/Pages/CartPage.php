<?php $cart = is_array($cart ?? null) ? $cart : []; ?>
<?php $items = is_array($cart['items'] ?? null) ? $cart['items'] : []; ?>
<?php $shippingQuote = is_array($cart['shipping_quote'] ?? null) ? $cart['shipping_quote'] : []; ?>
<?php $promotion = is_array($cart['promotion'] ?? null) ? $cart['promotion'] : []; ?>
<?php $promotionCatalog = is_array($cart['promotion_catalog'] ?? null) ? $cart['promotion_catalog'] : []; ?>
<?php $shippingRows = array_map(static fn(array $option): array => [
    'label' => (string) ($option['label'] ?? ''),
    'carrier' => (string) ($option['carrier_label'] ?? ''),
    'service' => (string) ($option['service_label'] ?? ''),
    'rate' => (string) ($option['effective_rate'] ?? ($option['rate'] ?? '')),
], is_array($shippingQuote['options'] ?? null) ? $shippingQuote['options'] : []); ?>
<?php $promotionRows = array_map(static fn(array $entry): array => [
    'code' => (string) ($entry['code'] ?? ''),
    'label' => (string) ($entry['label'] ?? ''),
    'effect' => (string) ($entry['effect'] ?? ''),
    'eligibility' => !empty($entry['eligible']) ? 'Eligible' : ((string) ($entry['message'] ?? 'Unavailable')),
], $promotionCatalog); ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'CartModule',
        'headline' => $headline ?? 'Cart',
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <div class="section">
        <h2>Items</h2>
        <?php if ($items === []): ?>
            <p>The cart is currently empty.</p>
            <p><a href="/shop">Browse the storefront catalog</a></p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Fulfillment</th>
                        <th>Unit price</th>
                        <th>Line total</th>
                        <th>Update quantity</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $entry = is_array($item) ? $item : []; ?>
                        <?php $slug = (string) ($entry['slug'] ?? ''); ?>
                        <?php $itemId = (int) ($entry['id'] ?? 0); ?>
                        <tr>
                            <td>
                                <?php if ($slug !== ''): ?>
                                    <a href="/shop/products/<?= $view->escapeUrl($slug) ?>"><?= $view->escape((string) ($entry['name'] ?? 'Product')) ?></a>
                                <?php else: ?>
                                    <?= $view->escape((string) ($entry['name'] ?? 'Product')) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $view->escape((string) ($entry['fulfillment_label'] ?? 'Physical shipping')) ?></td>
                            <td><?= $view->escape((string) ($entry['unit_price'] ?? '')) ?></td>
                            <td><?= $view->escape((string) ($entry['line_total'] ?? '')) ?></td>
                            <td>
                                <form method="post" action="/cart/items/<?= $itemId ?>/update">
                                    <label>
                                        Qty
                                        <input type="number" name="quantity" min="1" value="<?= (int) ($entry['quantity'] ?? 1) ?>">
                                    </label>
                                    <button type="submit">Update</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" action="/cart/items/<?= $itemId ?>/remove">
                                    <button type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Promotion code</h2>
        <form method="post" action="/cart/discount" class="stack">
            <label>
                Code
                <input type="text" name="coupon_code" value="<?= $view->escape((string) ($cart['discount_code'] ?? '')) ?>" placeholder="FRIFRAKT">
            </label>
            <div>
                <button type="submit">Apply code</button>
                <?php if (($cart['discount_code'] ?? '') !== ''): ?>
                    <button type="submit" formaction="/cart/discount/remove">Remove code</button>
                <?php endif; ?>
            </div>
        </form>

        <?php if (($cart['discount_code'] ?? '') !== ''): ?>
            <p>
                Applied: <strong><?= $view->escape((string) ($cart['discount_label'] ?? $cart['discount_code'] ?? 'Promotion')) ?></strong>
                (<?= $view->escape((string) ($cart['discount_code'] ?? '')) ?>)
            </p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Cart summary</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Cart ID' => $cart['id'] ?? '',
                'Status' => $cart['status'] ?? '',
                'Currency' => $cart['currency'] ?? '',
                'Items' => $cart['item_count'] ?? 0,
                'Fulfillment types' => implode(', ', (array) ($cart['fulfillment']['types'] ?? [])),
                'Shipping country' => $cart['shipping_country'] ?? '',
                'Shipping zone' => $cart['shipping_zone'] ?? '',
                'Shipping option' => $cart['shipping_option_label'] ?? '',
                'Carrier' => $cart['shipping_carrier_label'] ?? '',
                'Promotion code' => $cart['discount_code'] ?? '',
                'Subtotal' => $cart['subtotal'] ?? '',
                'Discount' => $cart['discount'] ?? '',
                'Shipping before discount' => $cart['shipping_base'] ?? '',
                'Shipping discount' => $cart['shipping_discount'] ?? '',
                'Shipping' => $cart['shipping'] ?? '',
                'Tax' => $cart['tax'] ?? '',
                'Total' => $cart['total'] ?? '',
            ],
        ]) ?>

        <p>
            <a href="/shop">Continue shopping</a>
            <?php if ($items !== []): ?>
                | <a href="/orders/checkout">Proceed to checkout</a>
            <?php endif; ?>
        </p>
    </div>

    <?php if ($shippingRows !== []): ?>
        <div class="section">
            <h2>Shipping preview</h2>
            <?= $view->renderComponent('DataTable', [
                'columns' => [
                    'label' => 'Option',
                    'carrier' => 'Carrier',
                    'service' => 'Service',
                    'rate' => 'Rate',
                ],
                'rows' => $shippingRows,
                'empty' => 'No shipping preview is currently available.',
            ]) ?>
        </div>
    <?php endif; ?>

    <?php if ($promotionRows !== []): ?>
        <div class="section">
            <h2>Available promotions</h2>
            <?= $view->renderComponent('DataTable', [
                'columns' => [
                    'code' => 'Code',
                    'label' => 'Offer',
                    'effect' => 'Effect',
                    'eligibility' => 'Status',
                ],
                'rows' => $promotionRows,
                'empty' => 'No promotions are currently configured.',
            ]) ?>
        </div>
    <?php endif; ?>
</section>
