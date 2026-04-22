<?php

declare(strict_types=1);

namespace App\Modules\OrderModule\Migrations;

use App\Abstracts\Database\Migration;

class AddOrderShipmentTrackingColumns extends Migration
{
    public static function dependencies(): array
    {
        return [
            AddOrderCommerceStateColumns::class,
        ];
    }

    public function up(): void
    {
        $this->addColumn('orders', 'shipping_country', 'VARCHAR(8)', ['default' => 'SE']);
        $this->addColumn('orders', 'shipping_zone', 'VARCHAR(24)', ['default' => 'SE']);
        $this->addColumn('orders', 'shipping_option', 'VARCHAR(80)', ['default' => 'postnord-service-point']);
        $this->addColumn('orders', 'shipping_option_label', 'VARCHAR(191)', ['default' => 'PostNord Service Point']);
        $this->addColumn('orders', 'shipping_carrier', 'VARCHAR(64)', ['default' => 'postnord']);
        $this->addColumn('orders', 'shipping_carrier_label', 'VARCHAR(191)', ['default' => 'PostNord']);
        $this->addColumn('orders', 'shipping_service', 'VARCHAR(80)', ['default' => 'service_point']);
        $this->addColumn('orders', 'shipping_service_label', 'VARCHAR(191)', ['default' => 'Service Point']);
        $this->addColumn('orders', 'shipping_service_point_id', 'VARCHAR(120)', ['nullable' => true]);
        $this->addColumn('orders', 'shipping_service_point_name', 'VARCHAR(191)', ['nullable' => true]);
        $this->addColumn('orders', 'tracking_number', 'VARCHAR(191)', ['nullable' => true]);
        $this->addColumn('orders', 'tracking_url', 'VARCHAR(255)', ['nullable' => true]);
        $this->addColumn('orders', 'shipment_reference', 'VARCHAR(191)', ['nullable' => true]);
        $this->addColumn('orders', 'tracking_events', 'JSON', ['nullable' => true]);
        $this->addColumn('orders', 'shipped_at', 'TIMESTAMP', ['nullable' => true]);
        $this->addColumn('orders', 'delivered_at', 'TIMESTAMP', ['nullable' => true]);
    }

    public function down(): void
    {
        $this->dropColumn('orders', 'delivered_at');
        $this->dropColumn('orders', 'shipped_at');
        $this->dropColumn('orders', 'tracking_events');
        $this->dropColumn('orders', 'shipment_reference');
        $this->dropColumn('orders', 'tracking_url');
        $this->dropColumn('orders', 'tracking_number');
        $this->dropColumn('orders', 'shipping_service_point_name');
        $this->dropColumn('orders', 'shipping_service_point_id');
        $this->dropColumn('orders', 'shipping_service_label');
        $this->dropColumn('orders', 'shipping_service');
        $this->dropColumn('orders', 'shipping_carrier_label');
        $this->dropColumn('orders', 'shipping_carrier');
        $this->dropColumn('orders', 'shipping_option_label');
        $this->dropColumn('orders', 'shipping_option');
        $this->dropColumn('orders', 'shipping_zone');
        $this->dropColumn('orders', 'shipping_country');
    }
}
