<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Commerce;

use App\Contracts\Support\CarrierAdapterInterface;
use App\Core\Config;
use App\Providers\ShippingProvider;
use App\Utilities\Traits\MoneyFormattingTrait;

class ShippingManager
{
    use MoneyFormattingTrait;

    private ShippingProvider $provider;

    public function __construct(private readonly Config $config, ?ShippingProvider $provider = null)
    {
        $this->provider = $provider ?? new ShippingProvider();
        $this->provider->registerServices();
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function quote(array $items, string $currency = 'SEK', array $context = []): array
    {
        $currency = strtoupper(trim($currency)) !== ''
            ? strtoupper(trim($currency))
            : strtoupper((string) $this->config->get('commerce', 'CURRENCY', 'SEK'));

        $country = $this->normalizeCountryCode((string) ($context['country'] ?? $this->defaultCountry()));
        $zone = $this->zoneForCountry($country);
        $subtotalMinor = array_reduce(
            $items,
            static fn(int $carry, array $item): int => $carry + max(0, (int) ($item['line_total_minor'] ?? 0)),
            0
        );
        $fulfillment = $this->fulfillmentProfile($items);

        $requestedCode = trim((string) ($context['shipping_option'] ?? $context['option'] ?? ''));
        $options = $this->availableOptions($country, $currency, $subtotalMinor, $fulfillment);
        $selected = $this->selectOption($options, $requestedCode)
            ?? (!$fulfillment['requires_shipping'] ? $this->nonShippingOption($currency, $country, $zone) : null)
            ?? $this->selectOption($options, $this->defaultOptionCode($country))
            ?? ($options[0] ?? $this->fallbackOption($currency, $country, $zone, $subtotalMinor));

        return [
            'currency' => $currency,
            'country' => $country,
            'zone' => $zone,
            'subtotal_minor' => $subtotalMinor,
            'fulfillment' => $fulfillment,
            'selected' => $selected,
            'options' => $options,
            'carriers' => $this->carrierCatalog($country),
            'tracking_apps' => $this->trackingApps((string) ($selected['carrier_code'] ?? ''), $country),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function carrierCatalog(?string $country = null): array
    {
        $country = $country !== null && $country !== ''
            ? $this->normalizeCountryCode($country)
            : $this->defaultCountry();

        $zone = $this->zoneForCountry($country);
        $carriers = array_replace_recursive($this->defaultCarriers(), $this->configuredCarriers());
        $results = [];

        foreach ($carriers as $code => $carrier) {
            $carrierCode = strtolower((string) ($carrier['code'] ?? $code));
            $zones = array_map('strtoupper', array_map('strval', (array) ($carrier['zones'] ?? [])));

            if ($zones !== [] && !in_array($zone, $zones, true)) {
                continue;
            }

            $results[] = [
                'code' => $carrierCode,
                'label' => (string) ($carrier['label'] ?? ucfirst($carrierCode)),
                'portal_url' => (string) ($carrier['portal_url'] ?? ''),
                'service_levels' => array_values(array_map('strval', (array) ($carrier['service_levels'] ?? []))),
                'supports_service_points' => (bool) ($carrier['supports_service_points'] ?? false),
                'tracking_apps' => $this->trackingApps($carrierCode, $country),
            ];
        }

        return $results;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function adapterCatalog(?string $country = null): array
    {
        $catalog = [];
        $country = $country !== null && $country !== ''
            ? $this->normalizeCountryCode($country)
            : $this->defaultCountry();

        foreach ($this->carrierCatalog($country) as $carrier) {
            $carrierCode = (string) ($carrier['code'] ?? '');
            $adapter = $this->carrierAdapter($carrierCode);

            if (!$adapter instanceof CarrierAdapterInterface) {
                continue;
            }

            $catalog[$carrierCode] = [
                ...$adapter->capabilities(),
                'carrier' => $carrierCode,
                'tracking_apps' => $this->trackingApps($carrierCode, $country),
            ];
        }

        return $catalog;
    }

    /**
     * @param array<string, mixed> $quote
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function orderSnapshot(array $quote, array $payload = []): array
    {
        $selected = is_array($quote['selected'] ?? null) ? $quote['selected'] : [];
        $servicePointId = trim((string) ($payload['service_point_id'] ?? ''));
        $servicePointName = trim((string) ($payload['service_point_name'] ?? ''));

        return [
            'shipping_country' => (string) ($quote['country'] ?? $this->defaultCountry()),
            'shipping_zone' => (string) ($quote['zone'] ?? 'SE'),
            'shipping_option' => (string) ($selected['code'] ?? $this->defaultOptionCode((string) ($quote['country'] ?? 'SE'))),
            'shipping_option_label' => (string) ($selected['label'] ?? 'Shipping'),
            'shipping_carrier' => (string) ($selected['carrier_code'] ?? ''),
            'shipping_carrier_label' => (string) ($selected['carrier_label'] ?? ''),
            'shipping_service' => (string) ($selected['service_code'] ?? ''),
            'shipping_service_label' => (string) ($selected['service_label'] ?? ''),
            'shipping_service_point_id' => $servicePointId,
            'shipping_service_point_name' => $servicePointName,
            'tracking_number' => '',
            'tracking_url' => (string) ($selected['tracking_portal_url'] ?? ''),
            'shipment_reference' => '',
            'tracking_events' => json_encode([], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'shipped_at' => null,
            'delivered_at' => null,
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function prepareShipmentUpdate(array $order, array $payload = []): array
    {
        $country = $this->normalizeCountryCode((string) ($order['shipping_country'] ?? $this->defaultCountry()));
        $carrierCode = strtolower(trim((string) ($payload['carrier_code'] ?? $order['shipping_carrier'] ?? '')));
        $carrier = $this->carrierByCode($carrierCode);

        if ($carrier === null) {
            return [
                'successful' => false,
                'status' => 422,
                'title' => 'Shipment update failed',
                'message' => 'Choose a valid carrier before marking the order as shipped.',
            ];
        }

        $bookingAttributes = [];
        $trackingNumber = trim((string) ($payload['tracking_number'] ?? $order['tracking_number'] ?? ''));

        if ($trackingNumber === '' && $this->shouldAutoBookShipment($payload)) {
            $booking = $this->bookShipment($order, $payload);

            if (($booking['successful'] ?? false) === false) {
                return $booking;
            }

            $bookingAttributes = is_array($booking['attributes'] ?? null) ? $booking['attributes'] : [];
            $trackingNumber = trim((string) ($bookingAttributes['tracking_number'] ?? ''));
        }

        if ($trackingNumber === '') {
            return [
                'successful' => false,
                'status' => 422,
                'title' => 'Shipment update failed',
                'message' => 'A tracking number or shipment booking is required before the order can be marked as shipped.',
            ];
        }

        $shipmentReference = trim((string) ($payload['shipment_reference'] ?? $bookingAttributes['shipment_reference'] ?? $order['shipment_reference'] ?? ''));

        if ($shipmentReference === '') {
            $shipmentReference = $this->generateShipmentReference((string) ($order['order_number'] ?? 'ORD'));
        }

        $servicePointId = trim((string) ($payload['service_point_id'] ?? $bookingAttributes['shipping_service_point_id'] ?? $order['shipping_service_point_id'] ?? ''));
        $servicePointName = trim((string) ($payload['service_point_name'] ?? $bookingAttributes['shipping_service_point_name'] ?? $order['shipping_service_point_name'] ?? ''));
        $events = $bookingAttributes !== [] && isset($bookingAttributes['tracking_events'])
            ? $this->decodeTrackingEvents((string) $bookingAttributes['tracking_events'])
            : $this->normalizeTrackingEvents((array) ($order['tracking_events'] ?? []));
        $events[] = [
            'status' => 'shipped',
            'label' => sprintf('Shipment handed to %s.', (string) ($carrier['label'] ?? ucfirst($carrierCode))),
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => $servicePointName !== '' ? $servicePointName : $country,
            'tracking_number' => $trackingNumber,
            'shipment_reference' => $shipmentReference,
            'carrier_code' => $carrierCode,
        ];

        return [
            'successful' => true,
            'status' => 200,
            'attributes' => [
                ...$bookingAttributes,
                'shipping_carrier' => $carrierCode,
                'shipping_carrier_label' => (string) ($carrier['label'] ?? ucfirst($carrierCode)),
                'tracking_number' => $trackingNumber,
                'tracking_url' => (string) ($carrier['portal_url'] ?? ''),
                'shipment_reference' => $shipmentReference,
                'shipping_service_point_id' => $servicePointId,
                'shipping_service_point_name' => $servicePointName,
                'tracking_events' => json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'shipped_at' => gmdate('Y-m-d H:i:s'),
            ],
            'carrier' => $carrier,
            'tracking_apps' => $this->trackingApps($carrierCode, $country),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function servicePoints(array $context, array $payload = []): array
    {
        $country = $this->normalizeCountryCode((string) ($payload['country'] ?? $context['shipping_country'] ?? $this->defaultCountry()));
        $carrierCode = strtolower(trim((string) ($payload['carrier_code'] ?? $context['shipping_carrier'] ?? '')));
        $carrier = $this->carrierByCode($carrierCode);

        if ($carrier === null) {
            return [
                'successful' => false,
                'status' => 422,
                'title' => 'Service point lookup failed',
                'message' => 'Choose a valid carrier before looking up pickup or service points.',
                'service_points' => [],
            ];
        }

        $adapter = $this->carrierAdapter($carrierCode);

        if ($adapter instanceof CarrierAdapterInterface) {
            return $adapter->servicePoints([
                ...$context,
                'country' => $country,
                'carrier' => $carrier,
            ], $payload);
        }

        if (!(bool) ($carrier['supports_service_points'] ?? false)) {
            return [
                'successful' => true,
                'status' => 200,
                'title' => 'No service points required',
                'message' => (string) ($carrier['label'] ?? ucfirst($carrierCode)) . ' does not require service-point selection for this service.',
                'carrier' => $carrier,
                'service_points' => [],
            ];
        }

        $postalCode = strtoupper(preg_replace('/\s+/', '', trim((string) ($payload['postal_code'] ?? $context['shipping_postal_code'] ?? '11122'))) ?? '11122');
        $city = trim((string) ($payload['city'] ?? $context['shipping_city'] ?? 'Stockholm'));
        $city = $city !== '' ? $city : 'Stockholm';
        $serviceLevel = strtolower(trim((string) ($payload['service_level'] ?? $context['shipping_service'] ?? 'service_point')));
        $prefix = $this->carrierReferencePrefix($carrierCode);
        $seed = substr(preg_replace('/[^A-Z0-9]+/', '', $postalCode . strtoupper($city)) ?: 'SE', 0, 8);
        $points = [];

        foreach ([1, 2, 3] as $index) {
            $points[] = [
                'id' => sprintf('%s-SP-%s-%02d', $prefix, $seed, $index),
                'label' => sprintf('%s %s %s %d', (string) ($carrier['label'] ?? ucfirst($carrierCode)), $serviceLevel === 'locker' ? 'Locker' : 'Service Point', $city, $index),
                'carrier_code' => $carrierCode,
                'carrier_label' => (string) ($carrier['label'] ?? ucfirst($carrierCode)),
                'service_level' => $serviceLevel !== '' ? $serviceLevel : 'service_point',
                'address_line' => sprintf('%s %d', $serviceLevel === 'locker' ? 'Locker Street' : 'Pickup Street', $index),
                'postal_code' => $postalCode,
                'city' => $city,
                'country' => $country,
                'distance_meters' => $index * 350,
                'cutoff_time' => sprintf('%02d:00', 16 + $index),
                'supports_locker' => in_array($carrierCode, ['instabox', 'budbee'], true) || $serviceLevel === 'locker',
            ];
        }

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Service points available',
            'message' => 'Reference service points are available for the selected carrier.',
            'carrier' => $carrier,
            'service_points' => $points,
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function bookShipment(array $order, array $payload = []): array
    {
        $country = $this->normalizeCountryCode((string) ($order['shipping_country'] ?? $this->defaultCountry()));
        $carrierCode = strtolower(trim((string) ($payload['carrier_code'] ?? $order['shipping_carrier'] ?? '')));
        $carrier = $this->carrierByCode($carrierCode);

        if ($carrier === null) {
            return [
                'successful' => false,
                'status' => 422,
                'title' => 'Shipment booking failed',
                'message' => 'Choose a valid carrier before booking the shipment.',
            ];
        }

        $adapter = $this->carrierAdapter($carrierCode);

        if ($adapter instanceof CarrierAdapterInterface) {
            $result = $adapter->bookShipment([
                ...$order,
                'shipping_country' => $country,
                'carrier' => $carrier,
            ], $payload);
            $result['tracking_apps'] ??= $this->trackingApps($carrierCode, $country);

            return $result;
        }

        $shipmentReference = trim((string) ($payload['shipment_reference'] ?? ''));
        $shipmentReference = $shipmentReference !== ''
            ? $shipmentReference
            : $this->generateShipmentReference((string) ($order['order_number'] ?? 'ORD'));
        $trackingNumber = trim((string) ($payload['tracking_number'] ?? ''));
        $trackingNumber = $trackingNumber !== ''
            ? $trackingNumber
            : $this->generateTrackingNumber($carrierCode, $shipmentReference);
        $labelReference = trim((string) ($payload['label_reference'] ?? ''));
        $labelReference = $labelReference !== ''
            ? $labelReference
            : sprintf('LBL-%s', $shipmentReference);
        $labelFormat = strtolower(trim((string) ($payload['label_format'] ?? $this->config->get('commerce', 'SHIPPING.INTEGRATION.LABEL_FORMAT', 'pdf'))));
        $labelFormat = $labelFormat !== '' ? $labelFormat : 'pdf';
        $labelUrl = $this->labelUrl($carrierCode, $shipmentReference, $labelFormat);
        $servicePointId = trim((string) ($payload['service_point_id'] ?? $order['shipping_service_point_id'] ?? ''));
        $servicePointName = trim((string) ($payload['service_point_name'] ?? $order['shipping_service_point_name'] ?? ''));
        $events = $this->normalizeTrackingEvents((array) ($order['tracking_events'] ?? []));
        $events[] = [
            'status' => 'booked',
            'label' => sprintf('Shipment booked with %s.', (string) ($carrier['label'] ?? ucfirst($carrierCode))),
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => $servicePointName !== '' ? $servicePointName : $country,
            'tracking_number' => $trackingNumber,
            'shipment_reference' => $shipmentReference,
            'carrier_code' => $carrierCode,
            'label_reference' => $labelReference,
            'label_url' => $labelUrl,
            'label_format' => $labelFormat,
            'provider_reference' => sprintf('%s-%s', $this->carrierReferencePrefix($carrierCode), substr(hash('sha256', $shipmentReference), 0, 12)),
        ];

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Shipment booked',
            'message' => 'Shipment booking and label reference were prepared successfully.',
            'attributes' => [
                'shipping_carrier' => $carrierCode,
                'shipping_carrier_label' => (string) ($carrier['label'] ?? ucfirst($carrierCode)),
                'tracking_number' => $trackingNumber,
                'tracking_url' => (string) ($carrier['portal_url'] ?? ''),
                'shipment_reference' => $shipmentReference,
                'shipping_service_point_id' => $servicePointId,
                'shipping_service_point_name' => $servicePointName,
                'tracking_events' => json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ],
            'carrier' => $carrier,
            'tracking_apps' => $this->trackingApps($carrierCode, $country),
            'label_reference' => $labelReference,
            'label_url' => $labelUrl,
            'label_format' => $labelFormat,
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function syncTracking(array $order, array $payload = []): array
    {
        $trackingNumber = trim((string) ($order['tracking_number'] ?? $payload['tracking_number'] ?? ''));

        if ($trackingNumber === '') {
            return [
                'successful' => false,
                'status' => 422,
                'title' => 'Tracking sync failed',
                'message' => 'A shipment must be booked or assigned a tracking number before tracking can be synced.',
            ];
        }

        $carrierCode = strtolower(trim((string) ($order['shipping_carrier'] ?? $payload['carrier_code'] ?? '')));
        $adapter = $this->carrierAdapter($carrierCode);

        if ($adapter instanceof CarrierAdapterInterface) {
            return $adapter->syncTracking($order, $payload);
        }

        $events = $this->normalizeTrackingEvents((array) ($order['tracking_events'] ?? []));
        $status = strtolower(trim((string) ($payload['tracking_status'] ?? $payload['status'] ?? '')));
        $status = $status !== '' ? $status : $this->nextTrackingStatus($events);
        $status = str_replace('-', '_', $status);
        $label = trim((string) ($payload['label'] ?? ''));
        $label = $label !== '' ? $label : $this->labelForTrackingStatus($status);
        $location = trim((string) ($payload['location'] ?? ''));
        $location = $location !== ''
            ? $location
            : (string) ($order['shipping_service_point_name'] ?? $order['shipping_country'] ?? $this->defaultCountry());
        $carrierCode = strtolower(trim((string) ($order['shipping_carrier'] ?? $payload['carrier_code'] ?? '')));
        $shipmentReference = trim((string) ($order['shipment_reference'] ?? $payload['shipment_reference'] ?? ''));

        $events[] = [
            'status' => $status,
            'label' => $label,
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => $location,
            'tracking_number' => $trackingNumber,
            'shipment_reference' => $shipmentReference,
            'carrier_code' => $carrierCode,
        ];

        $attributes = [
            'tracking_events' => json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];

        if ($status === 'delivered') {
            $attributes['delivered_at'] = gmdate('Y-m-d H:i:s');
        }

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Tracking synced',
            'message' => 'Tracking status was synced successfully.',
            'attributes' => $attributes,
            'terminal' => $status === 'delivered',
            'tracking_status' => $status,
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function cancelShipment(array $order, array $payload = []): array
    {
        $trackingNumber = trim((string) ($order['tracking_number'] ?? ''));
        $shipmentReference = trim((string) ($order['shipment_reference'] ?? ''));

        if ($trackingNumber === '' && $shipmentReference === '') {
            return [
                'successful' => false,
                'status' => 422,
                'title' => 'Shipment cancellation failed',
                'message' => 'There is no booked shipment or tracking reference to cancel.',
            ];
        }

        $carrierCode = strtolower(trim((string) ($order['shipping_carrier'] ?? '')));
        $adapter = $this->carrierAdapter($carrierCode);

        if ($adapter instanceof CarrierAdapterInterface) {
            return $adapter->cancelShipment($order, $payload);
        }

        $reason = trim((string) ($payload['reason'] ?? 'Operator cancelled the shipment booking.'));
        $events = $this->normalizeTrackingEvents((array) ($order['tracking_events'] ?? []));
        $events[] = [
            'status' => 'cancelled',
            'label' => $reason !== '' ? $reason : 'Shipment booking cancelled.',
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => (string) ($order['shipping_country'] ?? $this->defaultCountry()),
            'tracking_number' => $trackingNumber,
            'shipment_reference' => $shipmentReference,
            'carrier_code' => $carrierCode,
        ];

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Shipment cancelled',
            'message' => 'Shipment booking was cancelled and the order can be re-booked.',
            'attributes' => [
                'tracking_number' => '',
                'tracking_url' => '',
                'shipment_reference' => '',
                'tracking_events' => json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'shipped_at' => null,
                'delivered_at' => null,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    public function markDelivered(array $order): array
    {
        $events = $this->normalizeTrackingEvents((array) ($order['tracking_events'] ?? []));
        $events[] = [
            'status' => 'delivered',
            'label' => 'Shipment delivered to the customer.',
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => (string) ($order['shipping_country'] ?? $this->defaultCountry()),
            'tracking_number' => (string) ($order['tracking_number'] ?? ''),
        ];

        return [
            'successful' => true,
            'status' => 200,
            'attributes' => [
                'tracking_events' => json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'delivered_at' => gmdate('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    public function presentation(array $order): array
    {
        $carrierCode = (string) ($order['shipping_carrier'] ?? '');
        $country = $this->normalizeCountryCode((string) ($order['shipping_country'] ?? $this->defaultCountry()));
        $trackingEvents = $this->normalizeTrackingEvents((array) ($order['tracking_events'] ?? []));
        $label = $this->latestShipmentLabel($trackingEvents);

        return [
            'shipping_country' => $country,
            'shipping_zone' => (string) ($order['shipping_zone'] ?? $this->zoneForCountry($country)),
            'shipping_option' => (string) ($order['shipping_option'] ?? ''),
            'shipping_option_label' => (string) ($order['shipping_option_label'] ?? ''),
            'shipping_carrier' => $carrierCode,
            'shipping_carrier_label' => (string) ($order['shipping_carrier_label'] ?? ''),
            'shipping_service' => (string) ($order['shipping_service'] ?? ''),
            'shipping_service_label' => (string) ($order['shipping_service_label'] ?? ''),
            'shipping_service_point_id' => (string) ($order['shipping_service_point_id'] ?? ''),
            'shipping_service_point_name' => (string) ($order['shipping_service_point_name'] ?? ''),
            'tracking_number' => (string) ($order['tracking_number'] ?? ''),
            'tracking_url' => (string) ($order['tracking_url'] ?? ''),
            'shipment_reference' => (string) ($order['shipment_reference'] ?? ''),
            'shipment_label_reference' => (string) ($label['label_reference'] ?? ''),
            'shipment_label_url' => (string) ($label['label_url'] ?? ''),
            'shipment_label_format' => (string) ($label['label_format'] ?? ''),
            'shipped_at' => (string) ($order['shipped_at'] ?? ''),
            'delivered_at' => (string) ($order['delivered_at'] ?? ''),
            'tracking_events' => $trackingEvents,
            'tracking_apps' => $this->trackingApps($carrierCode, $country),
        ];
    }

    public function defaultCountry(): string
    {
        return $this->normalizeCountryCode((string) $this->config->get('commerce', 'SHIPPING.DEFAULT_COUNTRY', 'SE'));
    }

    public function defaultOptionCode(?string $country = null): string
    {
        $configured = trim((string) $this->config->get('commerce', 'SHIPPING.DEFAULT_OPTION', 'postnord-service-point'));

        if ($configured !== '') {
            return strtolower($configured);
        }

        return $this->zoneForCountry($country ?? $this->defaultCountry()) === 'SE'
            ? 'postnord-service-point'
            : 'dhl-express';
    }

    private function carrierByCode(string $carrierCode): ?array
    {
        foreach (array_replace_recursive($this->defaultCarriers(), $this->configuredCarriers()) as $code => $carrier) {
            $resolvedCode = strtolower((string) ($carrier['code'] ?? $code));

            if ($resolvedCode === strtolower($carrierCode)) {
                return [
                    'code' => $resolvedCode,
                    'label' => (string) ($carrier['label'] ?? ucfirst($resolvedCode)),
                    'portal_url' => (string) ($carrier['portal_url'] ?? ''),
                    'service_levels' => array_values(array_map('strval', (array) ($carrier['service_levels'] ?? []))),
                    'supports_service_points' => (bool) ($carrier['supports_service_points'] ?? false),
                ];
            }
        }

        return null;
    }

    private function carrierAdapter(string $carrierCode): ?CarrierAdapterInterface
    {
        $carrierCode = strtolower(trim($carrierCode));

        if ($carrierCode === '') {
            return null;
        }

        try {
            return $this->provider->getCarrierAdapter($carrierCode, $this->carrierAdapterSettings($carrierCode));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function carrierAdapterSettings(string $carrierCode): array
    {
        $global = $this->config->get('commerce', 'SHIPPING.INTEGRATION', []);
        $global = is_array($global) ? $this->normalizeConfigKeys($global) : [];
        $carrier = $this->carrierByCode($carrierCode) ?? [];
        $adapters = $this->configuredAdapters();
        $adapter = is_array($adapters[$carrierCode] ?? null) ? $adapters[$carrierCode] : [];

        return array_replace_recursive($global, $carrier, $adapter, [
            'code' => $carrierCode,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function availableOptions(string $country, string $currency, int $subtotalMinor, array $fulfillment = []): array
    {
        $zone = $this->zoneForCountry($country);
        $freeShippingOverMinor = max(0, (int) $this->config->get('commerce', 'SHIPPING.FREE_OVER_MINOR', 0));
        $options = array_replace_recursive($this->defaultOptions(), $this->configuredOptions());
        $results = [];
        $requiresShipping = (bool) ($fulfillment['requires_shipping'] ?? true);

        if (!$requiresShipping) {
            $results[] = $this->nonShippingOption($currency, $country, $zone);
        }

        foreach ($options as $code => $option) {
            if (!$requiresShipping) {
                continue;
            }

            $optionCode = strtolower((string) ($option['code'] ?? $code));
            $zones = array_map('strtoupper', array_map('strval', (array) ($option['zones'] ?? [])));

            if ($zones !== [] && !in_array($zone, $zones, true)) {
                continue;
            }

            $countries = array_map([$this, 'normalizeCountryCode'], array_map('strval', (array) ($option['countries'] ?? [])));

            if ($countries !== [] && !in_array($country, $countries, true)) {
                continue;
            }

            $carrierCode = strtolower((string) ($option['carrier'] ?? ''));
            $carrier = $this->carrierByCode($carrierCode);
            $baseRateMinor = max(0, (int) ($option['rate_minor'] ?? 0));
            $effectiveRateMinor = $this->isEligibleForFreeShipping($option, $subtotalMinor, $freeShippingOverMinor)
                ? 0
                : $baseRateMinor;

            $results[] = [
                'code' => $optionCode,
                'label' => (string) ($option['label'] ?? ucfirst(str_replace('-', ' ', $optionCode))),
                'carrier_code' => $carrierCode,
                'carrier_label' => (string) ($carrier['label'] ?? ucfirst($carrierCode)),
                'service_code' => (string) ($option['service_code'] ?? $optionCode),
                'service_label' => (string) ($option['service_label'] ?? (string) ($option['label'] ?? ucfirst($optionCode))),
                'rate_minor' => $baseRateMinor,
                'effective_rate_minor' => $effectiveRateMinor,
                'rate' => $this->formatMoneyMinor($baseRateMinor, $currency),
                'effective_rate' => $this->formatMoneyMinor($effectiveRateMinor, $currency),
                'service_point_required' => (bool) ($option['service_point_required'] ?? false),
                'service_level' => (string) ($option['service_level'] ?? ''),
                'free_shipping_eligible' => (bool) ($option['free_shipping_eligible'] ?? true),
                'tracking_portal_url' => (string) ($carrier['portal_url'] ?? ''),
                'tracking_apps' => $this->trackingApps($carrierCode, $country),
                'zones' => $zones,
            ];
        }

        foreach ($this->pickupOptions($country, $currency, $zone) as $pickupOption) {
            $results[] = $pickupOption;
        }

        return array_values($results);
    }

    /**
     * @param list<array<string, mixed>> $options
     * @return array<string, mixed>|null
     */
    private function selectOption(array $options, string $requestedCode): ?array
    {
        if ($requestedCode === '') {
            return null;
        }

        foreach ($options as $option) {
            if (strcasecmp((string) ($option['code'] ?? ''), $requestedCode) === 0) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function fulfillmentProfile(array $items): array
    {
        $defaultType = strtolower(trim((string) $this->config->get('commerce', 'FULFILLMENT.DEFAULT_TYPE', 'physical_shipping')));
        $shippingTypes = array_map('strtolower', array_map('strval', (array) $this->config->get('commerce', 'FULFILLMENT.SHIPPING_REQUIRED_TYPES', ['physical_shipping', 'preorder'])));
        $pickupTypes = array_map('strtolower', array_map('strval', (array) $this->config->get('commerce', 'FULFILLMENT.PICKUP_TYPES', ['store_pickup', 'scheduled_pickup'])));
        $stockManagedTypes = array_map('strtolower', array_map('strval', (array) $this->config->get('commerce', 'FULFILLMENT.STOCK_MANAGED_TYPES', ['physical_shipping', 'store_pickup', 'scheduled_pickup'])));
        $types = [];

        foreach ($items as $item) {
            $type = strtolower(trim((string) ($item['fulfillment_type'] ?? $defaultType)));
            $types[] = $type !== '' ? $type : $defaultType;
        }

        $types = array_values(array_unique($types));
        $requiresShipping = $types === [] || array_intersect($types, $shippingTypes) !== [];
        $allowsPickup = array_intersect($types, $shippingTypes) !== [] || array_intersect($types, $pickupTypes) !== [];
        $requiresStock = array_intersect($types, $stockManagedTypes) !== [];

        return [
            'types' => $types,
            'primary_type' => $types[0] ?? $defaultType,
            'requires_shipping' => $requiresShipping,
            'allows_pickup' => $allowsPickup,
            'requires_stock' => $requiresStock,
            'is_mixed' => count($types) > 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nonShippingOption(string $currency, string $country, string $zone): array
    {
        $configured = $this->config->get('commerce', 'FULFILLMENT.DIGITAL_DELIVERY_OPTION', []);
        $configured = is_array($configured) ? array_change_key_case($configured, CASE_LOWER) : [];
        $code = strtolower((string) ($configured['code'] ?? 'digital-delivery'));
        $label = (string) ($configured['label'] ?? 'Digital / online delivery');
        $serviceLabel = (string) ($configured['service_label'] ?? 'Instant access after payment');

        return [
            'code' => $code,
            'label' => $label,
            'carrier_code' => '',
            'carrier_label' => '',
            'service_code' => 'digital_delivery',
            'service_label' => $serviceLabel,
            'rate_minor' => 0,
            'effective_rate_minor' => 0,
            'rate' => $this->formatMoneyMinor(0, $currency),
            'effective_rate' => $this->formatMoneyMinor(0, $currency),
            'service_point_required' => false,
            'service_level' => 'digital',
            'free_shipping_eligible' => false,
            'tracking_portal_url' => '',
            'tracking_apps' => [],
            'zones' => [$zone],
            'countries' => [$country],
            'fulfillment_method' => 'digital_delivery',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pickupOptions(string $country, string $currency, string $zone): array
    {
        $configured = $this->config->get('commerce', 'FULFILLMENT.PICKUP_OPTIONS', []);
        $configured = is_array($configured) ? $this->normalizeConfigKeys($configured) : [];
        $results = [];

        foreach ($configured as $code => $option) {
            if (!is_array($option)) {
                continue;
            }

            $zones = array_map('strtoupper', array_map('strval', (array) ($option['zones'] ?? [])));
            if ($zones !== [] && !in_array($zone, $zones, true)) {
                continue;
            }

            $countries = array_map([$this, 'normalizeCountryCode'], array_map('strval', (array) ($option['countries'] ?? [])));
            if ($countries !== [] && !in_array($country, $countries, true)) {
                continue;
            }

            $optionCode = strtolower((string) ($option['code'] ?? $code));
            $rateMinor = max(0, (int) ($option['rate_minor'] ?? 0));
            $serviceCode = (string) ($option['service_code'] ?? $optionCode);

            $results[] = [
                'code' => $optionCode,
                'label' => (string) ($option['label'] ?? ucfirst(str_replace('-', ' ', $optionCode))),
                'carrier_code' => '',
                'carrier_label' => '',
                'service_code' => $serviceCode,
                'service_label' => (string) ($option['service_label'] ?? (string) ($option['label'] ?? 'Pickup')),
                'rate_minor' => $rateMinor,
                'effective_rate_minor' => $rateMinor,
                'rate' => $this->formatMoneyMinor($rateMinor, $currency),
                'effective_rate' => $this->formatMoneyMinor($rateMinor, $currency),
                'service_point_required' => (bool) ($option['location_required'] ?? true),
                'service_level' => $serviceCode,
                'free_shipping_eligible' => false,
                'tracking_portal_url' => '',
                'tracking_apps' => [],
                'zones' => $zones,
                'countries' => $countries,
                'fulfillment_method' => $serviceCode,
                'schedule_required' => (bool) ($option['schedule_required'] ?? false),
            ];
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackOption(string $currency, string $country, string $zone, int $subtotalMinor): array
    {
        $baseRateMinor = max(0, (int) $this->config->get('commerce', 'SHIPPING.FLAT_RATE_MINOR', 0));
        $freeShippingOverMinor = max(0, (int) $this->config->get('commerce', 'SHIPPING.FREE_OVER_MINOR', 0));
        $effectiveRateMinor = $subtotalMinor >= $freeShippingOverMinor && $freeShippingOverMinor > 0 ? 0 : $baseRateMinor;

        return [
            'code' => 'standard',
            'label' => 'Standard Shipping',
            'carrier_code' => 'postnord',
            'carrier_label' => 'PostNord',
            'service_code' => 'standard',
            'service_label' => 'Standard Shipping',
            'rate_minor' => $baseRateMinor,
            'effective_rate_minor' => $effectiveRateMinor,
            'rate' => $this->formatMoneyMinor($baseRateMinor, $currency),
            'effective_rate' => $this->formatMoneyMinor($effectiveRateMinor, $currency),
            'service_point_required' => false,
            'service_level' => 'standard',
            'free_shipping_eligible' => true,
            'tracking_portal_url' => 'https://www.postnord.se/en/track-and-trace',
            'tracking_apps' => $this->trackingApps('postnord', $country),
            'zones' => [$zone],
        ];
    }

    private function isEligibleForFreeShipping(array $option, int $subtotalMinor, int $freeShippingOverMinor): bool
    {
        if (!(bool) ($option['free_shipping_eligible'] ?? true)) {
            return false;
        }

        return $freeShippingOverMinor > 0 && $subtotalMinor >= $freeShippingOverMinor;
    }

    private function zoneForCountry(string $country): string
    {
        return match ($country) {
            'SE' => 'SE',
            'DK', 'FI', 'NO' => 'NORDIC',
            'AT', 'BE', 'BG', 'CH', 'CY', 'CZ', 'DE', 'EE', 'ES', 'FR', 'GR', 'HR', 'HU', 'IE', 'IS', 'IT', 'LI', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SI', 'SK' => 'EU',
            default => 'INTL',
        };
    }

    private function normalizeCountryCode(string $country): string
    {
        $normalized = strtoupper(trim($country));

        return match ($normalized) {
            '', 'SWEDEN', 'SVERIGE', 'SWE' => 'SE',
            'DENMARK', 'DANMARK' => 'DK',
            'FINLAND', 'SUOMI' => 'FI',
            'NORWAY', 'NORGE' => 'NO',
            default => strlen($normalized) > 2 ? substr($normalized, 0, 2) : $normalized,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function trackingApps(string $carrierCode, string $country): array
    {
        $apps = array_replace_recursive($this->defaultTrackingApps(), $this->configuredTrackingApps());
        $results = [];

        foreach ($apps as $appCode => $app) {
            $countries = array_map([$this, 'normalizeCountryCode'], array_map('strval', (array) ($app['countries'] ?? [])));
            $carriers = array_map('strtolower', array_map('strval', (array) ($app['carriers'] ?? [])));

            if ($countries !== [] && !in_array($country, $countries, true)) {
                continue;
            }

            if ($carriers !== [] && !in_array(strtolower($carrierCode), $carriers, true)) {
                continue;
            }

            $results[] = [
                'code' => strtolower((string) ($app['code'] ?? $appCode)),
                'label' => (string) ($app['label'] ?? ucfirst($appCode)),
                'platforms' => array_values(array_map('strval', (array) ($app['platforms'] ?? []))),
                'note' => (string) ($app['note'] ?? ''),
            ];
        }

        return $results;
    }

    private function generateShipmentReference(string $orderNumber): string
    {
        return sprintf('SHP-%s-%s', preg_replace('/[^A-Z0-9]+/', '', strtoupper($orderNumber)) ?: 'ORDER', strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
    }

    private function generateTrackingNumber(string $carrierCode, string $shipmentReference): string
    {
        return sprintf('%s%sSE', $this->carrierReferencePrefix($carrierCode), strtoupper(substr(hash('sha256', $shipmentReference), 0, 10)));
    }

    private function carrierReferencePrefix(string $carrierCode): string
    {
        return match (strtolower($carrierCode)) {
            'postnord' => 'PN',
            'instabox' => 'IB',
            'budbee' => 'BB',
            'bring' => 'BR',
            'dhl' => 'DHL',
            'schenker' => 'SCH',
            'earlybird' => 'EB',
            'airmee' => 'AM',
            'ups' => 'UPS',
            default => strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $carrierCode) ?: 'CAR', 0, 3)),
        };
    }

    private function shouldAutoBookShipment(array $payload): bool
    {
        if (array_key_exists('book_label', $payload) || array_key_exists('book_shipment', $payload)) {
            return filter_var($payload['book_label'] ?? $payload['book_shipment'], FILTER_VALIDATE_BOOLEAN);
        }

        return filter_var($this->config->get('commerce', 'SHIPPING.INTEGRATION.AUTO_BOOK_LABELS', true), FILTER_VALIDATE_BOOLEAN);
    }

    private function labelUrl(string $carrierCode, string $shipmentReference, string $labelFormat): string
    {
        $base = trim((string) $this->config->get('commerce', 'SHIPPING.INTEGRATION.LABEL_BASE_URL', 'https://shipments.langelermvc.test/labels'));

        if ($base === '') {
            return '';
        }

        return rtrim($base, '/') . '/' . rawurlencode($carrierCode) . '/' . rawurlencode($shipmentReference) . '.' . rawurlencode($labelFormat);
    }

    /**
     * @return list<array<string, string>>
     */
    private function decodeTrackingEvents(string $payload): array
    {
        try {
            $events = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($events) ? $this->normalizeTrackingEvents($events) : [];
    }

    /**
     * @param array<int, mixed> $events
     * @return list<array<string, string>>
     */
    private function normalizeTrackingEvents(array $events): array
    {
        $normalized = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $normalized[] = [
                'status' => (string) ($event['status'] ?? ''),
                'label' => (string) ($event['label'] ?? ''),
                'occurred_at' => (string) ($event['occurred_at'] ?? ''),
                'location' => (string) ($event['location'] ?? ''),
                'tracking_number' => (string) ($event['tracking_number'] ?? ''),
                'shipment_reference' => (string) ($event['shipment_reference'] ?? ''),
                'carrier_code' => (string) ($event['carrier_code'] ?? ''),
                'label_reference' => (string) ($event['label_reference'] ?? ''),
                'label_url' => (string) ($event['label_url'] ?? ''),
                'label_format' => (string) ($event['label_format'] ?? ''),
                'provider_reference' => (string) ($event['provider_reference'] ?? ''),
            ];
        }

        return $normalized;
    }

    /**
     * @param list<array<string, string>> $events
     * @return array<string, string>
     */
    private function latestShipmentLabel(array $events): array
    {
        for ($index = count($events) - 1; $index >= 0; $index--) {
            $event = $events[$index];

            if (($event['status'] ?? '') === 'cancelled') {
                return [];
            }

            if (($event['label_reference'] ?? '') !== '' || ($event['label_url'] ?? '') !== '') {
                return $event;
            }
        }

        return [];
    }

    /**
     * @param list<array<string, string>> $events
     */
    private function nextTrackingStatus(array $events): string
    {
        $lastStatus = '';

        foreach ($events as $event) {
            $status = (string) ($event['status'] ?? '');

            if ($status !== '') {
                $lastStatus = $status;
            }
        }

        return match ($lastStatus) {
            'booked', 'shipped' => 'in_transit',
            'in_transit' => 'out_for_delivery',
            'out_for_delivery' => 'delivered',
            default => 'in_transit',
        };
    }

    private function labelForTrackingStatus(string $status): string
    {
        return match ($status) {
            'booked' => 'Shipment has been booked with the carrier.',
            'shipped' => 'Shipment has been handed to the carrier.',
            'in_transit' => 'Shipment is in transit.',
            'out_for_delivery' => 'Shipment is out for delivery.',
            'delivered' => 'Shipment has been delivered.',
            'cancelled' => 'Shipment booking has been cancelled.',
            default => 'Tracking status updated.',
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function configuredCarriers(): array
    {
        $configured = $this->config->get('commerce', 'SHIPPING.CARRIERS', []);

        return is_array($configured) ? $this->normalizeConfigKeys($configured) : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function configuredOptions(): array
    {
        $configured = $this->config->get('commerce', 'SHIPPING.OPTIONS', []);

        return is_array($configured) ? $this->normalizeConfigKeys($configured) : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function configuredTrackingApps(): array
    {
        $configured = $this->config->get('commerce', 'SHIPPING.TRACKING_APPS', []);

        return is_array($configured) ? $this->normalizeConfigKeys($configured) : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function configuredAdapters(): array
    {
        $configured = $this->config->get('commerce', 'SHIPPING.ADAPTERS', []);

        return is_array($configured) ? $this->normalizeConfigKeys($configured) : [];
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function normalizeConfigKeys(array $value): array
    {
        $normalized = [];

        foreach ($value as $key => $item) {
            $resolvedKey = is_string($key) ? strtolower($key) : $key;
            $normalized[$resolvedKey] = is_array($item)
                ? $this->normalizeConfigKeys($item)
                : $item;
        }

        return $normalized;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultCarriers(): array
    {
        return [
            'postnord' => [
                'code' => 'postnord',
                'label' => 'PostNord',
                'portal_url' => 'https://www.postnord.se/en/track-and-trace',
                'zones' => ['SE', 'NORDIC', 'EU'],
                'service_levels' => ['service_point', 'home'],
                'supports_service_points' => true,
            ],
            'instabox' => [
                'code' => 'instabox',
                'label' => 'Instabox',
                'portal_url' => 'https://instabox.io',
                'zones' => ['SE'],
                'service_levels' => ['locker'],
                'supports_service_points' => true,
            ],
            'budbee' => [
                'code' => 'budbee',
                'label' => 'Budbee',
                'portal_url' => 'https://budbee.com',
                'zones' => ['SE', 'NORDIC'],
                'service_levels' => ['locker', 'home'],
                'supports_service_points' => true,
            ],
            'bring' => [
                'code' => 'bring',
                'label' => 'Bring',
                'portal_url' => 'https://www.bring.se',
                'zones' => ['SE', 'NORDIC', 'EU'],
                'service_levels' => ['service_point', 'home'],
                'supports_service_points' => true,
            ],
            'dhl' => [
                'code' => 'dhl',
                'label' => 'DHL',
                'portal_url' => 'https://www.dhl.com/se-en/home/tracking.html',
                'zones' => ['SE', 'NORDIC', 'EU', 'INTL'],
                'service_levels' => ['service_point', 'home', 'express'],
                'supports_service_points' => true,
            ],
            'schenker' => [
                'code' => 'schenker',
                'label' => 'Schenker',
                'portal_url' => 'https://www.dbschenker.com/se-en',
                'zones' => ['SE', 'NORDIC', 'EU'],
                'service_levels' => ['service_point', 'home'],
                'supports_service_points' => true,
            ],
            'earlybird' => [
                'code' => 'earlybird',
                'label' => 'Early Bird',
                'portal_url' => 'https://earlybird.se',
                'zones' => ['SE'],
                'service_levels' => ['mailbox'],
                'supports_service_points' => false,
            ],
            'airmee' => [
                'code' => 'airmee',
                'label' => 'Airmee',
                'portal_url' => 'https://airmee.com',
                'zones' => ['SE'],
                'service_levels' => ['home'],
                'supports_service_points' => false,
            ],
            'ups' => [
                'code' => 'ups',
                'label' => 'UPS',
                'portal_url' => 'https://www.ups.com/track',
                'zones' => ['SE', 'NORDIC', 'EU', 'INTL'],
                'service_levels' => ['home', 'standard', 'express'],
                'supports_service_points' => false,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultOptions(): array
    {
        return [
            'postnord-service-point' => [
                'code' => 'postnord-service-point',
                'label' => 'PostNord Service Point',
                'carrier' => 'postnord',
                'service_code' => 'service_point',
                'service_label' => 'Service Point',
                'zones' => ['SE'],
                'rate_minor' => 1490,
                'service_point_required' => true,
                'free_shipping_eligible' => true,
                'service_level' => 'standard',
            ],
            'instabox-locker' => [
                'code' => 'instabox-locker',
                'label' => 'Instabox Locker',
                'carrier' => 'instabox',
                'service_code' => 'locker',
                'service_label' => 'Locker Delivery',
                'zones' => ['SE'],
                'rate_minor' => 990,
                'service_point_required' => true,
                'free_shipping_eligible' => true,
                'service_level' => 'standard',
            ],
            'budbee-box' => [
                'code' => 'budbee-box',
                'label' => 'Budbee Box',
                'carrier' => 'budbee',
                'service_code' => 'locker',
                'service_label' => 'Locker Delivery',
                'zones' => ['SE'],
                'rate_minor' => 1090,
                'service_point_required' => true,
                'free_shipping_eligible' => true,
                'service_level' => 'standard',
            ],
            'budbee-home' => [
                'code' => 'budbee-home',
                'label' => 'Budbee Home',
                'carrier' => 'budbee',
                'service_code' => 'home',
                'service_label' => 'Home Delivery',
                'zones' => ['SE', 'NORDIC'],
                'rate_minor' => 1290,
                'service_point_required' => false,
                'free_shipping_eligible' => false,
                'service_level' => 'home',
            ],
            'bring-service-point' => [
                'code' => 'bring-service-point',
                'label' => 'Bring Service Point',
                'carrier' => 'bring',
                'service_code' => 'service_point',
                'service_label' => 'Service Point',
                'zones' => ['SE', 'NORDIC'],
                'rate_minor' => 1390,
                'service_point_required' => true,
                'free_shipping_eligible' => true,
                'service_level' => 'standard',
            ],
            'dhl-service-point' => [
                'code' => 'dhl-service-point',
                'label' => 'DHL Service Point',
                'carrier' => 'dhl',
                'service_code' => 'service_point',
                'service_label' => 'Service Point',
                'zones' => ['SE', 'NORDIC', 'EU'],
                'rate_minor' => 1590,
                'service_point_required' => true,
                'free_shipping_eligible' => true,
                'service_level' => 'standard',
            ],
            'schenker-parcel' => [
                'code' => 'schenker-parcel',
                'label' => 'Schenker Parcel',
                'carrier' => 'schenker',
                'service_code' => 'parcel',
                'service_label' => 'Parcel Delivery',
                'zones' => ['SE', 'NORDIC', 'EU'],
                'rate_minor' => 1490,
                'service_point_required' => false,
                'free_shipping_eligible' => true,
                'service_level' => 'standard',
            ],
            'earlybird-mailbox' => [
                'code' => 'earlybird-mailbox',
                'label' => 'Early Bird Mailbox',
                'carrier' => 'earlybird',
                'service_code' => 'mailbox',
                'service_label' => 'Mailbox Delivery',
                'zones' => ['SE'],
                'rate_minor' => 790,
                'service_point_required' => false,
                'free_shipping_eligible' => true,
                'service_level' => 'economy',
            ],
            'airmee-home' => [
                'code' => 'airmee-home',
                'label' => 'Airmee Home',
                'carrier' => 'airmee',
                'service_code' => 'home',
                'service_label' => 'Evening Home Delivery',
                'zones' => ['SE'],
                'rate_minor' => 1690,
                'service_point_required' => false,
                'free_shipping_eligible' => false,
                'service_level' => 'express',
            ],
            'ups-standard' => [
                'code' => 'ups-standard',
                'label' => 'UPS Standard',
                'carrier' => 'ups',
                'service_code' => 'standard',
                'service_label' => 'Standard Delivery',
                'zones' => ['EU', 'INTL'],
                'rate_minor' => 2490,
                'service_point_required' => false,
                'free_shipping_eligible' => false,
                'service_level' => 'standard',
            ],
            'dhl-express' => [
                'code' => 'dhl-express',
                'label' => 'DHL Express',
                'carrier' => 'dhl',
                'service_code' => 'express',
                'service_label' => 'Express Delivery',
                'zones' => ['EU', 'INTL'],
                'rate_minor' => 2990,
                'service_point_required' => false,
                'free_shipping_eligible' => false,
                'service_level' => 'express',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultTrackingApps(): array
    {
        return [
            'mina_paket' => [
                'code' => 'mina_paket',
                'label' => 'Mina Paket',
                'countries' => ['SE'],
                'carriers' => ['postnord', 'instabox', 'budbee', 'bring', 'dhl', 'schenker', 'earlybird', 'airmee', 'ups'],
                'platforms' => ['iOS'],
                'note' => 'Helpful for Swedish parcel tracking when the tracking number is imported into the app.',
            ],
        ];
    }
}
