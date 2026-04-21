<?php $cart = is_array($cart ?? null) ? $cart : []; ?>
<?php $payment = is_array($payment ?? null) ? $payment : []; ?>
<?php $checkout = is_array($checkout ?? null) ? $checkout : []; ?>
<?php $lookup = is_array($lookup ?? null) ? $lookup : []; ?>
<?php $items = is_array($cart['items'] ?? null) ? $cart['items'] : []; ?>
<section class="stack">
    <?= $view->renderPartial('PageIntro', [
        'eyebrow' => 'OrderModule',
        'headline' => $headline ?? 'Checkout',
        'summary' => $summary ?? '',
    ]) ?>

    <?php if (($message ?? '') !== ''): ?>
        <?= $view->renderPartial('StatusMessage', ['message' => $message]) ?>
    <?php endif; ?>

    <?php if ($items === []): ?>
        <div class="section">
            <h2>Checkout unavailable</h2>
            <p>There are no cart items available for checkout yet.</p>
            <p><a href="/shop">Browse the storefront catalog</a></p>
        </div>
    <?php else: ?>
    <div class="section">
        <h2>Checkout cart</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'name' => 'Product',
                'quantity' => 'Qty',
                'unit_price' => 'Unit price',
                'line_total' => 'Line total',
            ],
            'rows' => is_array($cart['items'] ?? null) ? $cart['items'] : [],
            'empty' => 'There are no items available for checkout.',
        ]) ?>
    </div>

    <div class="section">
        <h2>Customer and delivery details</h2>
        <form method="post" action="/orders/checkout" class="stack">
            <label>
                Full name
                <input type="text" name="name" value="<?= $view->escape((string) ($checkout['name'] ?? '')) ?>" required>
            </label>

            <label>
                Email address
                <input type="email" name="email" value="<?= $view->escape((string) ($checkout['email'] ?? '')) ?>" required>
            </label>

            <label>
                Phone
                <input type="text" name="phone" value="<?= $view->escape((string) ($checkout['phone'] ?? '')) ?>">
            </label>

            <label>
                Address line 1
                <input type="text" name="line_one" value="<?= $view->escape((string) ($checkout['line_one'] ?? '')) ?>" required>
            </label>

            <label>
                Address line 2
                <input type="text" name="line_two" value="<?= $view->escape((string) ($checkout['line_two'] ?? '')) ?>">
            </label>

            <label>
                Postal code
                <input type="text" name="postal_code" value="<?= $view->escape((string) ($checkout['postal_code'] ?? '')) ?>" required>
            </label>

            <label>
                City
                <input type="text" name="city" value="<?= $view->escape((string) ($checkout['city'] ?? '')) ?>" required>
            </label>

            <label>
                Country
                <input type="text" name="country" value="<?= $view->escape((string) ($checkout['country'] ?? 'SE')) ?>" required>
            </label>

            <label>
                Payment driver
                <select name="payment_driver">
                    <?php foreach ((array) ($payment['available_drivers'] ?? []) as $driver): ?>
                        <option value="<?= $view->escape((string) $driver) ?>"<?php if (($checkout['payment_driver'] ?? $payment['driver'] ?? '') === $driver): ?> selected<?php endif; ?>>
                            <?= $view->escape((string) $driver) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Payment method
                <select name="payment_method">
                    <?php foreach ((array) ($payment['supported_methods'] ?? []) as $method): ?>
                        <option value="<?= $view->escape((string) $method) ?>"<?php if (($checkout['payment_method'] ?? $payment['default_method'] ?? '') === $method): ?> selected<?php endif; ?>>
                            <?= $view->escape((string) $method) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Payment flow
                <select name="payment_flow">
                    <?php foreach ((array) ($payment['supported_flows'] ?? []) as $flow): ?>
                        <option value="<?= $view->escape((string) $flow) ?>"<?php if (($checkout['payment_flow'] ?? $payment['default_flow'] ?? '') === $flow): ?> selected<?php endif; ?>>
                            <?= $view->escape((string) $flow) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Idempotency key
                <input type="text" name="idempotency_key" value="<?= $view->escape((string) ($checkout['idempotency_key'] ?? '')) ?>" placeholder="Optional integration test key">
            </label>

            <div>
                <button type="submit">Place order</button>
                <a href="/cart">Return to cart</a>
            </div>
        </form>
    </div>

    <div class="section">
        <h2>Summary</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Currency' => $cart['currency'] ?? '',
                'Items' => $cart['item_count'] ?? 0,
                'Subtotal' => $cart['subtotal'] ?? '',
                'Payment driver' => $payment['driver'] ?? '',
                'Default method' => $payment['default_method'] ?? '',
                'Default flow' => $payment['default_flow'] ?? '',
            ],
        ]) ?>
    </div>

    <div class="section">
        <h2>Payment compatibility</h2>
        <?= $view->renderComponent('DefinitionGrid', [
            'items' => [
                'Available drivers' => implode(', ', (array) ($payment['available_drivers'] ?? [])),
                'Supported methods' => implode(', ', (array) ($payment['supported_methods'] ?? [])),
                'Supported flows' => implode(', ', (array) ($payment['supported_flows'] ?? [])),
                'Completion return URL' => $lookup['complete_url'] ?? '/orders/complete',
                'Cancellation return URL' => $lookup['cancelled_url'] ?? '/orders/cancelled',
            ],
        ]) ?>
    </div>

    <div class="section">
        <h2>Provider catalog</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'driver' => 'Driver',
                'label' => 'Label',
                'regions' => 'Regions',
                'methods' => 'Methods',
                'flows' => 'Flows',
                'mode' => 'Mode',
            ],
            'rows' => array_map(static function (array $provider): array {
                return [
                    'driver' => $provider['driver'] ?? '',
                    'label' => $provider['label'] ?? '',
                    'regions' => implode(', ', (array) ($provider['regions'] ?? [])),
                    'methods' => implode(', ', (array) ($provider['methods'] ?? [])),
                    'flows' => implode(', ', (array) ($provider['flows'] ?? [])),
                    'mode' => $provider['mode'] ?? '',
                ];
            }, is_array($payment['catalog'] ?? null) ? array_values($payment['catalog']) : []),
            'empty' => 'No payment providers are currently enabled.',
        ]) ?>
    </div>
    <?php endif; ?>
</section>
