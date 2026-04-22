<?php $cart = is_array($cart ?? null) ? $cart : []; ?>
<?php $items = is_array($cart['items'] ?? null) ? $cart['items'] : []; ?>
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
        <h2>Cart summary</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Cart ID' => $cart['id'] ?? '',
                'Status' => $cart['status'] ?? '',
                'Currency' => $cart['currency'] ?? '',
                'Items' => $cart['item_count'] ?? 0,
                'Subtotal' => $cart['subtotal'] ?? '',
                'Discount' => $cart['discount'] ?? '',
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
</section>
