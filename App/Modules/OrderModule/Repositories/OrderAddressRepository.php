<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Repositories;

use App\Abstracts\Database\Repository;
use App\Modules\OrderModule\Models\OrderAddress;

class OrderAddressRepository extends Repository
{
    protected string $modelClass = OrderAddress::class;

    /**
     * @return list<OrderAddress>
     */
    public function forOrder(int $orderId): array
    {
        return array_values(array_filter(
            $this->findBy(['order_id' => $orderId]),
            static fn(mixed $address): bool => $address instanceof OrderAddress
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function summaryForOrder(int $orderId): array
    {
        return array_map(function (OrderAddress $address): array {
            return [
                'type' => (string) ($address->getAttribute('type') ?? ''),
                'name' => (string) ($address->getAttribute('name') ?? ''),
                'line_one' => (string) ($address->getAttribute('line_one') ?? ''),
                'line_two' => (string) ($address->getAttribute('line_two') ?? ''),
                'postal_code' => (string) ($address->getAttribute('postal_code') ?? ''),
                'city' => (string) ($address->getAttribute('city') ?? ''),
                'country' => (string) ($address->getAttribute('country') ?? ''),
                'email' => (string) ($address->getAttribute('email') ?? ''),
                'phone' => (string) ($address->getAttribute('phone') ?? ''),
            ];
        }, $this->forOrder($orderId));
    }
}
