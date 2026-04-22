<?php

$cart = is_array($cart ?? null) ? $cart : [];
$payment = is_array($payment ?? null) ? $payment : [];
$checkout = is_array($checkout ?? null) ? $checkout : [];
$shipping = is_array($shipping ?? null) ? $shipping : [];
$lookup = is_array($lookup ?? null) ? $lookup : [];
$items = is_array($cart['items'] ?? null) ? $cart['items'] : [];
$providerRows = [];
$shippingRows = [];

foreach (is_array($payment['catalog'] ?? null) ? array_values($payment['catalog']) : [] as $provider) {
    $providerRows[] = [
        'driver' => $provider['driver'] ?? '',
        'label' => $provider['label'] ?? '',
        'regions' => implode(', ', (array) ($provider['regions'] ?? [])),
        'methods' => implode(', ', (array) ($provider['methods'] ?? [])),
        'flows' => implode(', ', (array) ($provider['flows'] ?? [])),
        'mode' => $provider['mode'] ?? '',
    ];
}

foreach (is_array($shipping['options'] ?? null) ? array_values($shipping['options']) : [] as $option) {
    $shippingRows[] = [
        'label' => $option['label'] ?? '',
        'carrier' => $option['carrier_label'] ?? '',
        'service' => $option['service_label'] ?? '',
        'rate' => $option['effective_rate'] ?? ($option['rate'] ?? ''),
        'service_point' => !empty($option['service_point_required']) ? 'Required' : 'Optional',
    ];
}
?>
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
        <h2>Customer, delivery, and shipping details</h2>
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
                Shipping option
                <select name="shipping_option">
                    <?php foreach ((array) ($shipping['options'] ?? []) as $option): ?>
                        <?php $entry = is_array($option) ? $option : []; ?>
                        <option value="<?= $view->escape((string) ($entry['code'] ?? '')) ?>"<?php if (($checkout['shipping_option'] ?? $shipping['selected_option'] ?? '') === ($entry['code'] ?? '')): ?> selected<?php endif; ?>>
                            <?= $view->escape((string) ($entry['label'] ?? 'Shipping')) ?> - <?= $view->escape((string) ($entry['effective_rate'] ?? ($entry['rate'] ?? ''))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Service point ID
                <input type="text" name="service_point_id" value="<?= $view->escape((string) ($checkout['service_point_id'] ?? $shipping['service_point_id'] ?? '')) ?>" placeholder="Optional for locker/service point deliveries">
            </label>

            <label>
                Service point name
                <input type="text" name="service_point_name" value="<?= $view->escape((string) ($checkout['service_point_name'] ?? $shipping['service_point_name'] ?? '')) ?>" placeholder="Optional delivery point name">
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
                'Shipping country' => $shipping['country'] ?? '',
                'Shipping zone' => $shipping['zone'] ?? '',
                'Selected shipping' => $shipping['selected_label'] ?? '',
                'Carrier' => $shipping['selected_carrier'] ?? '',
                'Service' => $shipping['selected_service'] ?? '',
                'Subtotal' => $cart['subtotal'] ?? '',
                'Discount' => $cart['discount'] ?? '',
                'Shipping' => $cart['shipping'] ?? '',
                'Tax' => $cart['tax'] ?? '',
                'Grand total' => $cart['total'] ?? '',
                'Payment driver' => $payment['driver'] ?? '',
                'Default method' => $payment['default_method'] ?? '',
                'Default flow' => $payment['default_flow'] ?? '',
            ],
        ]) ?>
    </div>

    <?php if (!empty($shipping['tracking_apps']) && is_array($shipping['tracking_apps'])): ?>
        <div class="section">
            <h2>Tracking apps</h2>
            <?= $view->renderComponent('DataTable', [
                'columns' => [
                    'label' => 'App',
                    'platforms' => 'Platforms',
                    'note' => 'Note',
                ],
                'rows' => array_map(static fn(array $app): array => [
                    'label' => (string) ($app['label'] ?? ''),
                    'platforms' => implode(', ', (array) ($app['platforms'] ?? [])),
                    'note' => (string) ($app['note'] ?? ''),
                ], $shipping['tracking_apps']),
                'empty' => 'No tracking apps are currently suggested for this shipment profile.',
            ]) ?>
        </div>
    <?php endif; ?>

    <div class="section">
        <h2>Shipping options</h2>
        <?= $view->renderComponent('DataTable', [
            'columns' => [
                'label' => 'Option',
                'carrier' => 'Carrier',
                'service' => 'Service',
                'rate' => 'Rate',
                'service_point' => 'Service point',
            ],
            'rows' => $shippingRows,
            'empty' => 'No shipping options are currently available for the selected destination.',
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
            'rows' => $providerRows,
            'empty' => 'No payment providers are currently enabled.',
        ]) ?>
    </div>
    <?php endif; ?>
</section>
