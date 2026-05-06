<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Commerce;

use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\AuditLoggerInterface;
use App\Core\Config;
use App\Modules\OrderModule\Models\Order;
use App\Modules\OrderModule\Repositories\OrderAddressRepository;
use App\Modules\OrderModule\Repositories\OrderDocumentRepository;
use App\Modules\OrderModule\Repositories\OrderItemRepository;
use App\Modules\OrderModule\Repositories\OrderRepository;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Traits\MoneyFormattingTrait;

class OrderDocumentManager
{
    use MoneyFormattingTrait;

    public function __construct(
        private readonly OrderDocumentRepository $documents,
        private readonly OrderRepository $orders,
        private readonly OrderItemRepository $orderItems,
        private readonly OrderAddressRepository $addresses,
        private readonly Config $config,
        private readonly EventDispatcherInterface $events,
        private readonly AuthManager $auth,
        private readonly AuditLoggerInterface $audit
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function issue(int $orderId, string $type, array $payload = []): array
    {
        $order = $this->orders->find($orderId);

        if (!$order instanceof Order) {
            return $this->failure('Document unavailable', 'The requested order could not be found.', 404);
        }

        $type = $this->normalizeType($type);
        $summary = $this->orders->mapSummary($order);
        $currency = (string) ($summary['currency'] ?? 'SEK');
        $vatRateBps = max(0, (int) ($payload['vat_rate_bps'] ?? $this->config->get('commerce', 'DOCUMENTS.VAT_RATE_BPS', $this->config->get('commerce', 'TAX.RATE_BPS', 2500))));
        $amount = $this->amountsForType($type, $summary, $payload, $vatRateBps);
        $seller = $this->sellerDetails($payload);
        $addresses = $this->addresses->summaryForOrder($orderId);
        $content = [
            'document_type' => $type,
            'order' => [
                'id' => $orderId,
                'order_number' => (string) ($summary['order_number'] ?? ''),
                'status' => (string) ($summary['status'] ?? ''),
                'payment_status' => (string) ($summary['payment_status'] ?? ''),
                'issued_at' => gmdate('Y-m-d H:i:s'),
            ],
            'seller' => $seller,
            'buyer' => [
                'name' => (string) ($summary['contact_name'] ?? ''),
                'email' => (string) ($summary['contact_email'] ?? ''),
                'addresses' => $addresses,
            ],
            'lines' => $this->documentLines($orderId, $currency),
            'amounts' => $amount,
            'vat' => [
                'rate_bps' => $vatRateBps,
                'tax_minor' => (int) ($amount['tax_minor'] ?? 0),
                'tax' => $this->formatMoneyMinor((int) ($amount['tax_minor'] ?? 0), $currency),
            ],
            'notes' => trim((string) ($payload['notes'] ?? '')),
        ];
        $document = $this->documents->issue([
            'order_id' => $orderId,
            'return_id' => max(0, (int) ($payload['return_id'] ?? 0)) ?: null,
            'type' => $type,
            'status' => 'issued',
            'currency' => $currency,
            'subtotal_minor' => (int) ($amount['subtotal_minor'] ?? 0),
            'discount_minor' => (int) ($amount['discount_minor'] ?? 0),
            'shipping_minor' => (int) ($amount['shipping_minor'] ?? 0),
            'tax_minor' => (int) ($amount['tax_minor'] ?? 0),
            'total_minor' => (int) ($amount['total_minor'] ?? 0),
            'vat_rate_bps' => $vatRateBps,
            'seller_name' => (string) ($seller['name'] ?? ''),
            'seller_vat_id' => (string) ($seller['vat_id'] ?? ''),
            'billing_country' => strtoupper(trim((string) ($payload['billing_country'] ?? $summary['shipping_country'] ?? 'SE'))),
            'notes' => trim((string) ($payload['notes'] ?? '')),
            'content' => $content,
        ]);

        $this->events->dispatch('order.document.issued', [
            'order_id' => $orderId,
            'document_id' => (int) $document->getKey(),
            'type' => $type,
        ]);
        $this->audit->record('order.document.issued', [
            'actor_id' => $this->auth->check() ? (string) $this->auth->id() : null,
            'order_id' => (string) $orderId,
            'document_id' => (string) $document->getKey(),
            'document_number' => (string) ($document->getAttribute('document_number') ?? ''),
            'type' => $type,
            'total_minor' => (int) ($document->getAttribute('total_minor') ?? 0),
        ], 'order');

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Order document issued',
            'message' => sprintf('%s document was issued for the order.', ucfirst(str_replace('_', ' ', $type))),
            'document' => $this->documents->mapSummary($document),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summariesForOrder(int $orderId): array
    {
        return $this->documents->summaryForOrder($orderId);
    }

    /**
     * @return array<string, int>
     */
    public function metrics(): array
    {
        return $this->documents->metrics();
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['invoice', 'credit_note', 'packing_slip', 'return_authorization'], true)
            ? $type
            : 'invoice';
    }

    /**
     * @param array<string, mixed> $summary
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function amountsForType(string $type, array $summary, array $payload, int $vatRateBps): array
    {
        $currency = (string) ($summary['currency'] ?? 'SEK');
        $subtotal = max(0, (int) ($summary['subtotal_minor'] ?? 0));
        $discount = max(0, (int) ($summary['discount_minor'] ?? 0));
        $shipping = max(0, (int) ($summary['shipping_minor'] ?? 0));
        $tax = max(0, (int) ($summary['tax_minor'] ?? 0));
        $total = max(0, (int) ($summary['total_minor'] ?? 0));

        if ($type === 'packing_slip' || $type === 'return_authorization') {
            return [
                'subtotal_minor' => 0,
                'discount_minor' => 0,
                'shipping_minor' => 0,
                'tax_minor' => 0,
                'total_minor' => 0,
                'subtotal' => $this->formatMoneyMinor(0, $currency),
                'discount' => $this->formatMoneyMinor(0, $currency),
                'shipping' => $this->formatMoneyMinor(0, $currency),
                'tax' => $this->formatMoneyMinor(0, $currency),
                'total' => $this->formatMoneyMinor(0, $currency),
            ];
        }

        if ($type === 'credit_note') {
            $requested = max(0, (int) ($payload['amount_minor'] ?? $payload['refund_minor'] ?? $total));
            $creditTotal = min($requested, $total);
            $creditTax = $vatRateBps > 0 ? (int) round($creditTotal * ($vatRateBps / (10000 + $vatRateBps))) : 0;
            $creditSubtotal = max(0, $creditTotal - $creditTax);

            return [
                'subtotal_minor' => $creditSubtotal,
                'discount_minor' => 0,
                'shipping_minor' => 0,
                'tax_minor' => $creditTax,
                'total_minor' => $creditTotal,
                'subtotal' => $this->formatMoneyMinor($creditSubtotal, $currency),
                'discount' => $this->formatMoneyMinor(0, $currency),
                'shipping' => $this->formatMoneyMinor(0, $currency),
                'tax' => $this->formatMoneyMinor($creditTax, $currency),
                'total' => $this->formatMoneyMinor($creditTotal, $currency),
            ];
        }

        return [
            'subtotal_minor' => $subtotal,
            'discount_minor' => $discount,
            'shipping_minor' => $shipping,
            'tax_minor' => $tax,
            'total_minor' => $total,
            'subtotal' => $this->formatMoneyMinor($subtotal, $currency),
            'discount' => $this->formatMoneyMinor($discount, $currency),
            'shipping' => $this->formatMoneyMinor($shipping, $currency),
            'tax' => $this->formatMoneyMinor($tax, $currency),
            'total' => $this->formatMoneyMinor($total, $currency),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{name:string,vat_id:string,address:string}
     */
    private function sellerDetails(array $payload): array
    {
        return [
            'name' => trim((string) ($payload['seller_name'] ?? $this->config->get('commerce', 'DOCUMENTS.SELLER_NAME', $this->config->get('app', 'NAME', 'LangelerMVC')))),
            'vat_id' => trim((string) ($payload['seller_vat_id'] ?? $this->config->get('commerce', 'DOCUMENTS.SELLER_VAT_ID', ''))),
            'address' => trim((string) ($payload['seller_address'] ?? $this->config->get('commerce', 'DOCUMENTS.SELLER_ADDRESS', ''))),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function documentLines(int $orderId, string $currency): array
    {
        return array_map(function (array $line) use ($currency): array {
            return [
                'order_item_id' => (int) ($line['id'] ?? 0),
                'product_id' => (int) ($line['product_id'] ?? 0),
                'name' => (string) ($line['name'] ?? ''),
                'quantity' => (int) ($line['quantity'] ?? 0),
                'unit_price_minor' => (int) ($line['unit_price_minor'] ?? 0),
                'line_total_minor' => (int) ($line['line_total_minor'] ?? 0),
                'unit_price' => $this->formatMoneyMinor((int) ($line['unit_price_minor'] ?? 0), $currency),
                'line_total' => $this->formatMoneyMinor((int) ($line['line_total_minor'] ?? 0), $currency),
                'fulfillment_type' => (string) ($line['fulfillment_type'] ?? ''),
            ];
        }, $this->orderItems->summaryForOrder($orderId));
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $title, string $message, int $status): array
    {
        return [
            'successful' => false,
            'status' => $status,
            'title' => $title,
            'message' => $message,
        ];
    }
}
