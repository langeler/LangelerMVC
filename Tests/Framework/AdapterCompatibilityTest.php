<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Abstracts\Support\CarrierAdapter;
use App\Contracts\Support\CarrierAdapterInterface;
use App\Contracts\Support\PaymentDriverInterface;
use App\Drivers\Shipping\PostNordCarrierAdapter;
use App\Providers\CoreProvider;
use App\Providers\PaymentProvider;
use App\Providers\ShippingProvider;
use App\Support\Commerce\ShippingManager;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentMethod;
use App\Support\Payments\PaymentResult;
use PHPUnit\Framework\TestCase;

final class AdapterCompatibilityTest extends TestCase
{
    public function testShippingManagerExposesFirstPartyCarrierAdapterCatalog(): void
    {
        $shipping = $this->shipping();
        $catalog = $shipping->adapterCatalog();

        foreach (['postnord', 'instabox', 'budbee', 'bring', 'dhl', 'schenker', 'earlybird', 'airmee', 'ups'] as $carrier) {
            self::assertArrayHasKey($carrier, $catalog);
            self::assertSame($carrier, $catalog[$carrier]['carrier']);
            self::assertSame('reference', $catalog[$carrier]['mode']);
            self::assertTrue((bool) $catalog[$carrier]['supports_booking']);
            self::assertTrue((bool) $catalog[$carrier]['supports_tracking']);
            self::assertTrue((bool) $catalog[$carrier]['supports_cancellation']);
            self::assertTrue((bool) $catalog[$carrier]['live_ready']);
        }
    }

    public function testReferenceCarrierAdaptersHandleOperationalLifecycle(): void
    {
        $shipping = $this->shipping();
        $lookup = $shipping->servicePoints([
            'shipping_carrier' => 'budbee',
            'shipping_country' => 'SE',
            'shipping_city' => 'Stockholm',
            'shipping_postal_code' => '111 22',
            'shipping_service' => 'locker',
        ]);

        self::assertTrue((bool) $lookup['successful']);
        self::assertSame('budbee', $lookup['carrier']['code']);
        self::assertCount(3, $lookup['service_points']);

        $booking = $shipping->bookShipment([
            'order_number' => 'ORD-ADAPTER-1',
            'shipping_country' => 'SE',
            'shipping_carrier' => 'budbee',
            'shipping_service_point_name' => 'Budbee Stockholm 1',
            'tracking_events' => [],
        ]);

        self::assertTrue((bool) $booking['successful']);
        self::assertSame('budbee', $booking['attributes']['shipping_carrier']);
        self::assertNotSame('', $booking['attributes']['tracking_number']);
        self::assertNotSame('', $booking['label_reference']);

        $tracking = $shipping->syncTracking([
            ...$booking['attributes'],
            'shipping_country' => 'SE',
            'shipping_carrier' => 'budbee',
        ], ['status' => 'delivered']);

        self::assertTrue((bool) $tracking['successful']);
        self::assertSame('delivered', $tracking['tracking_status']);
        self::assertArrayHasKey('delivered_at', $tracking['attributes']);

        $cancelled = $shipping->cancelShipment([
            ...$booking['attributes'],
            'shipping_country' => 'SE',
            'shipping_carrier' => 'budbee',
        ]);

        self::assertTrue((bool) $cancelled['successful']);
        self::assertSame('', $cancelled['attributes']['tracking_number']);
    }

    public function testLiveCarrierAdapterReadinessFailsClosedWithoutCredentials(): void
    {
        $adapter = (new PostNordCarrierAdapter())->configure([
            'MODE' => 'live',
            'LABEL' => 'PostNord',
            'REQUIRED_SETTINGS' => ['API_BASE', 'API_KEY', 'BOOKING_URL', 'TRACKING_URL'],
        ]);
        $readiness = $adapter->readiness();

        self::assertFalse((bool) $readiness['live_ready']);
        self::assertContains('API_BASE', $readiness['missing_required_settings']);
        self::assertContains('BOOKING_URL', $readiness['missing_required_settings']);

        $booking = $adapter->bookShipment([
            'order_number' => 'ORD-LIVE-MISSING',
            'shipping_country' => 'SE',
        ]);

        self::assertFalse((bool) $booking['successful']);
        self::assertSame(422, $booking['status']);
        self::assertStringContainsString('BOOKING_URL', $booking['message']);
    }

    public function testShippingProviderCanRegisterProjectSpecificCarrierAdapters(): void
    {
        $provider = new ShippingProvider();
        $provider->extendAdapter('demo_carrier', DemoCarrierAdapter::class);
        $provider->registerServices();
        $adapter = $provider->getCarrierAdapter('demo_carrier', ['MODE' => 'reference']);

        self::assertInstanceOf(CarrierAdapterInterface::class, $adapter);
        self::assertSame('demo_carrier', $adapter->carrierCode());
        self::assertTrue($adapter->supports('supports_booking'));
    }

    public function testShippingProviderConfiguresStandaloneCarrierAdaptersThroughContract(): void
    {
        $provider = new ShippingProvider();
        $provider->extendAdapter('standalone_carrier', StandaloneCarrierAdapter::class);
        $provider->registerServices();
        $adapter = $provider->getCarrierAdapter('standalone_carrier', ['MODE' => 'live']);

        self::assertInstanceOf(CarrierAdapterInterface::class, $adapter);
        self::assertSame('standalone_carrier', $adapter->carrierCode());
        self::assertSame('live', $adapter->capabilities()['mode']);
    }

    public function testPaymentProviderConfiguresStandalonePaymentDriversThroughContract(): void
    {
        $provider = new PaymentProvider();
        $provider->extendDriver('standalone_payment', StandalonePaymentDriver::class);
        $provider->registerServices();
        $driver = $provider->getPaymentDriver(['DRIVER' => 'standalone_payment', 'MODE' => 'live']);

        self::assertInstanceOf(PaymentDriverInterface::class, $driver);
        self::assertSame('standalone_payment', $driver->driverName());
        self::assertSame('live', $driver->capabilities()['mode']);
        self::assertTrue((bool) $driver->readiness()['live_ready']);
    }

    private function shipping(): ShippingManager
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $shipping = $provider->getCoreService('shipping');

        self::assertInstanceOf(ShippingManager::class, $shipping);

        return $shipping;
    }
}

final class DemoCarrierAdapter extends CarrierAdapter
{
    protected function defaultCarrierCode(): string
    {
        return 'demo_carrier';
    }
}

final class StandaloneCarrierAdapter implements CarrierAdapterInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $settings = [];

    public function carrierCode(): string
    {
        return strtolower((string) ($this->settings['CODE'] ?? 'standalone_carrier'));
    }

    public function configure(array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    public function capabilities(): array
    {
        return [
            'carrier' => $this->carrierCode(),
            'mode' => (string) ($this->settings['MODE'] ?? 'reference'),
            'supports_booking' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        return ($this->capabilities()[$feature] ?? false) === true;
    }

    public function readiness(): array
    {
        return ['live_ready' => true, 'missing_required_settings' => []];
    }

    public function servicePoints(array $context, array $payload = []): array
    {
        return ['successful' => true, 'service_points' => []];
    }

    public function bookShipment(array $order, array $payload = []): array
    {
        return ['successful' => true, 'attributes' => []];
    }

    public function syncTracking(array $order, array $payload = []): array
    {
        return ['successful' => true, 'attributes' => []];
    }

    public function cancelShipment(array $order, array $payload = []): array
    {
        return ['successful' => true, 'attributes' => []];
    }
}

final class StandalonePaymentDriver implements PaymentDriverInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $settings = [];

    public function driverName(): string
    {
        return 'standalone_payment';
    }

    public function configure(array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    public function capabilities(): array
    {
        return [
            'mode' => (string) ($this->settings['MODE'] ?? 'reference'),
            'methods' => $this->supportedMethods(),
            'flows' => $this->supportedFlows(),
            'live_ready' => true,
            'supports_authorize' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        return ($this->capabilities()[$feature] ?? false) === true;
    }

    public function readiness(): array
    {
        return ['live_ready' => true, 'missing_required_settings' => []];
    }

    public function supportedMethods(): array
    {
        return [PaymentMethod::Card->value];
    }

    public function supportedFlows(): array
    {
        return [PaymentFlow::Purchase->value];
    }

    public function supportsMethod(PaymentMethod|string $method): bool
    {
        return ($method instanceof PaymentMethod ? $method->value : (string) $method) === PaymentMethod::Card->value;
    }

    public function supportsFlow(PaymentFlow|string $flow): bool
    {
        return ($flow instanceof PaymentFlow ? $flow->value : (string) $flow) === PaymentFlow::Purchase->value;
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        return new PaymentResult(true, 'authorize', $intent, $this->driverName(), 'Authorized.', 'captured');
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        return new PaymentResult(true, 'capture', $intent, $this->driverName(), 'Captured.', 'captured');
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        return new PaymentResult(true, 'cancel', $intent, $this->driverName(), 'Cancelled.', 'cancelled');
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        return new PaymentResult(true, 'refund', $intent, $this->driverName(), 'Refunded.', 'refunded');
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        return new PaymentResult(true, 'reconcile', $intent, $this->driverName(), 'Reconciled.', 'captured');
    }
}
