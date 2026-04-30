<?php

declare(strict_types=1);

namespace App\Abstracts\Support;

use App\Contracts\Support\CarrierAdapterInterface;

abstract class CarrierAdapter implements CarrierAdapterInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $settings = [];

    public function configure(array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    public function carrierCode(): string
    {
        return $this->normalizeName($this->defaultCarrierCode());
    }

    public function capabilities(): array
    {
        $readiness = $this->readiness();

        return [
            'carrier' => $this->carrierCode(),
            'label' => $this->label(),
            'mode' => $this->mode(),
            'docs_url' => $this->docsUrl(),
            'portal_url' => $this->portalUrl(),
            'regions' => $this->regions(),
            'service_levels' => $this->serviceLevels(),
            'required_settings' => $this->requiredSettings(),
            'missing_required_settings' => $readiness['missing_required_settings'],
            'live_ready' => $readiness['live_ready'],
            'supports_service_points' => $this->supportsServicePoints(),
            'supports_booking' => true,
            'supports_labels' => true,
            'supports_tracking' => true,
            'supports_cancellation' => true,
            'reference_mode' => $this->mode() === 'reference',
        ];
    }

    public function supports(string $feature): bool
    {
        $value = $this->capabilities();

        foreach (explode('.', trim($feature)) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return $value === true;
    }

    public function readiness(): array
    {
        $missing = [];

        if ($this->isLiveMode()) {
            foreach ($this->requiredSettings() as $key) {
                $value = $this->setting($key);

                if ($value === null || (is_string($value) && trim($value) === '')) {
                    $missing[] = $key;
                }
            }
        }

        return [
            'carrier' => $this->carrierCode(),
            'mode' => $this->mode(),
            'live_ready' => !$this->isLiveMode() || $missing === [],
            'missing_required_settings' => $missing,
        ];
    }

    public function servicePoints(array $context, array $payload = []): array
    {
        if (!$this->supportsServicePoints()) {
            return [
                'successful' => true,
                'status' => 200,
                'title' => 'No service points required',
                'message' => $this->label() . ' does not require service-point selection for this service.',
                'carrier' => $this->carrierPayload(),
                'adapter' => $this->capabilitySummary(),
                'service_points' => [],
            ];
        }

        if ($this->isLiveMode()) {
            $endpoint = $this->endpointUrl('SERVICE_POINTS_URL');

            if ($endpoint === '') {
                return $this->missingEndpoint('Service point lookup failed', 'SERVICE_POINTS_URL');
            }

            $response = $this->requestJson('POST', $endpoint, [
                'context' => $context,
                'payload' => $payload,
            ]);

            if (!$this->responseSuccessful($response)) {
                return $this->providerFailure('Service point lookup failed', $response);
            }

            return [
                'successful' => true,
                'status' => 200,
                'title' => 'Service points available',
                'message' => 'Provider service points are available for the selected carrier.',
                'carrier' => $this->carrierPayload(),
                'adapter' => $this->capabilitySummary(),
                'service_points' => $this->normalizeServicePoints($response['json'], $context, $payload),
                'provider_payload' => $response['json'],
            ];
        }

        return $this->referenceServicePoints($context, $payload);
    }

    public function bookShipment(array $order, array $payload = []): array
    {
        if ($this->isLiveMode()) {
            $endpoint = $this->endpointUrl('BOOKING_URL');

            if ($endpoint === '') {
                return $this->missingEndpoint('Shipment booking failed', 'BOOKING_URL');
            }

            $response = $this->requestJson('POST', $endpoint, [
                'order' => $order,
                'payload' => $payload,
            ]);

            if (!$this->responseSuccessful($response)) {
                return $this->providerFailure('Shipment booking failed', $response);
            }

            return $this->normalizeBooking($order, $payload, $response['json']);
        }

        return $this->referenceBooking($order, $payload);
    }

    public function syncTracking(array $order, array $payload = []): array
    {
        if ($this->isLiveMode()) {
            $endpoint = $this->endpointUrl('TRACKING_URL');

            if ($endpoint === '') {
                return $this->missingEndpoint('Tracking sync failed', 'TRACKING_URL');
            }

            $response = $this->requestJson('POST', $endpoint, [
                'order' => $order,
                'payload' => $payload,
            ]);

            if (!$this->responseSuccessful($response)) {
                return $this->providerFailure('Tracking sync failed', $response);
            }

            return $this->normalizeTrackingUpdate($order, $payload, $response['json']);
        }

        return $this->referenceTrackingUpdate($order, $payload);
    }

    public function cancelShipment(array $order, array $payload = []): array
    {
        if ($this->isLiveMode()) {
            $endpoint = $this->endpointUrl('CANCELLATION_URL');

            if ($endpoint === '') {
                return $this->missingEndpoint('Shipment cancellation failed', 'CANCELLATION_URL');
            }

            $response = $this->requestJson('POST', $endpoint, [
                'order' => $order,
                'payload' => $payload,
            ]);

            if (!$this->responseSuccessful($response)) {
                return $this->providerFailure('Shipment cancellation failed', $response);
            }
        }

        return $this->referenceCancellation($order, $payload);
    }

    abstract protected function defaultCarrierCode(): string;

    protected function defaultLabel(): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $this->carrierCode()));
    }

    /**
     * @return list<string>
     */
    protected function defaultRegions(): array
    {
        return ['SE'];
    }

    /**
     * @return list<string>
     */
    protected function defaultServiceLevels(): array
    {
        return ['standard'];
    }

    protected function supportsServicePoints(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    protected function defaultRequiredSettings(): array
    {
        return ['API_BASE', 'API_KEY', 'BOOKING_URL', 'TRACKING_URL'];
    }

    protected function label(): string
    {
        return (string) $this->setting('LABEL', $this->defaultLabel());
    }

    protected function mode(): string
    {
        $mode = $this->normalizeName((string) $this->setting('MODE', 'reference'));

        return in_array($mode, ['reference', 'live'], true) ? $mode : 'reference';
    }

    protected function isLiveMode(): bool
    {
        return $this->mode() === 'live';
    }

    protected function docsUrl(): ?string
    {
        $url = trim((string) $this->setting('DOCS_URL', ''));

        return $url !== '' ? $url : null;
    }

    protected function portalUrl(): string
    {
        return (string) $this->setting('PORTAL_URL', '');
    }

    /**
     * @return list<string>
     */
    protected function regions(): array
    {
        return $this->stringList($this->setting('REGIONS', $this->setting('ZONES', $this->defaultRegions())));
    }

    /**
     * @return list<string>
     */
    protected function serviceLevels(): array
    {
        return $this->stringList($this->setting('SERVICE_LEVELS', $this->defaultServiceLevels()));
    }

    /**
     * @return list<string>
     */
    protected function requiredSettings(): array
    {
        $configured = $this->stringList($this->setting('REQUIRED_SETTINGS', []), preserveCase: true);

        return $configured !== [] ? $configured : $this->defaultRequiredSettings();
    }

    /**
     * @return array<string, mixed>
     */
    protected function carrierPayload(): array
    {
        return [
            'code' => $this->carrierCode(),
            'label' => $this->label(),
            'portal_url' => $this->portalUrl(),
            'service_levels' => $this->serviceLevels(),
            'supports_service_points' => $this->supportsServicePoints(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function capabilitySummary(): array
    {
        $capabilities = $this->capabilities();

        return [
            'carrier' => $capabilities['carrier'],
            'mode' => $capabilities['mode'],
            'live_ready' => $capabilities['live_ready'],
            'missing_required_settings' => $capabilities['missing_required_settings'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function referenceServicePoints(array $context, array $payload): array
    {
        $country = $this->country($payload['country'] ?? $context['shipping_country'] ?? $context['country'] ?? 'SE');
        $postalCode = strtoupper(preg_replace('/\s+/', '', trim((string) ($payload['postal_code'] ?? $context['shipping_postal_code'] ?? '11122'))) ?? '11122');
        $city = trim((string) ($payload['city'] ?? $context['shipping_city'] ?? 'Stockholm'));
        $city = $city !== '' ? $city : 'Stockholm';
        $serviceLevel = $this->normalizeName((string) ($payload['service_level'] ?? $context['shipping_service'] ?? 'service_point'));
        $prefix = $this->referencePrefix();
        $seed = substr(preg_replace('/[^A-Z0-9]+/', '', $postalCode . strtoupper($city)) ?: 'SE', 0, 8);
        $points = [];

        foreach ([1, 2, 3] as $index) {
            $locker = in_array($this->carrierCode(), ['instabox', 'budbee'], true) || $serviceLevel === 'locker';
            $points[] = [
                'id' => sprintf('%s-SP-%s-%02d', $prefix, $seed, $index),
                'label' => sprintf('%s %s %s %d', $this->label(), $locker ? 'Locker' : 'Service Point', $city, $index),
                'carrier_code' => $this->carrierCode(),
                'carrier_label' => $this->label(),
                'service_level' => $serviceLevel !== '' ? $serviceLevel : 'service_point',
                'address_line' => sprintf('%s %d', $locker ? 'Locker Street' : 'Pickup Street', $index),
                'postal_code' => $postalCode,
                'city' => $city,
                'country' => $country,
                'distance_meters' => $index * 350,
                'cutoff_time' => sprintf('%02d:00', 16 + $index),
                'supports_locker' => $locker,
            ];
        }

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Service points available',
            'message' => 'Reference service points are available for the selected carrier.',
            'carrier' => $this->carrierPayload(),
            'adapter' => $this->capabilitySummary(),
            'service_points' => $points,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function referenceBooking(array $order, array $payload): array
    {
        $country = $this->country($order['shipping_country'] ?? $payload['country'] ?? 'SE');
        $shipmentReference = trim((string) ($payload['shipment_reference'] ?? ''));
        $shipmentReference = $shipmentReference !== ''
            ? $shipmentReference
            : $this->generateShipmentReference((string) ($order['order_number'] ?? 'ORD'));
        $trackingNumber = trim((string) ($payload['tracking_number'] ?? ''));
        $trackingNumber = $trackingNumber !== ''
            ? $trackingNumber
            : $this->generateTrackingNumber($shipmentReference);
        $labelFormat = strtolower(trim((string) ($payload['label_format'] ?? $this->setting('LABEL_FORMAT', 'pdf'))));
        $labelFormat = $labelFormat !== '' ? $labelFormat : 'pdf';
        $labelReference = trim((string) ($payload['label_reference'] ?? ''));
        $labelReference = $labelReference !== '' ? $labelReference : sprintf('LBL-%s', $shipmentReference);
        $labelUrl = $this->labelUrl($shipmentReference, $labelFormat);
        $servicePointId = trim((string) ($payload['service_point_id'] ?? $order['shipping_service_point_id'] ?? ''));
        $servicePointName = trim((string) ($payload['service_point_name'] ?? $order['shipping_service_point_name'] ?? ''));
        $events = $this->trackingEvents($order['tracking_events'] ?? []);
        $events[] = [
            'status' => 'booked',
            'label' => sprintf('Shipment booked with %s.', $this->label()),
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => $servicePointName !== '' ? $servicePointName : $country,
            'tracking_number' => $trackingNumber,
            'shipment_reference' => $shipmentReference,
            'carrier_code' => $this->carrierCode(),
            'label_reference' => $labelReference,
            'label_url' => $labelUrl,
            'label_format' => $labelFormat,
            'provider_reference' => sprintf('%s-%s', $this->referencePrefix(), substr(hash('sha256', $shipmentReference), 0, 12)),
        ];

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Shipment booked',
            'message' => 'Shipment booking and label reference were prepared successfully.',
            'attributes' => [
                'shipping_carrier' => $this->carrierCode(),
                'shipping_carrier_label' => $this->label(),
                'tracking_number' => $trackingNumber,
                'tracking_url' => $this->portalUrl(),
                'shipment_reference' => $shipmentReference,
                'shipping_service_point_id' => $servicePointId,
                'shipping_service_point_name' => $servicePointName,
                'tracking_events' => json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ],
            'carrier' => $this->carrierPayload(),
            'adapter' => $this->capabilitySummary(),
            'label_reference' => $labelReference,
            'label_url' => $labelUrl,
            'label_format' => $labelFormat,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function referenceTrackingUpdate(array $order, array $payload): array
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

        $events = $this->trackingEvents($order['tracking_events'] ?? []);
        $status = $this->normalizeName((string) ($payload['tracking_status'] ?? $payload['status'] ?? ''));
        $status = $status !== '' ? $status : $this->nextTrackingStatus($events);
        $label = trim((string) ($payload['label'] ?? ''));
        $label = $label !== '' ? $label : $this->labelForTrackingStatus($status);
        $location = trim((string) ($payload['location'] ?? ''));
        $location = $location !== ''
            ? $location
            : (string) ($order['shipping_service_point_name'] ?? $order['shipping_country'] ?? 'SE');
        $shipmentReference = trim((string) ($order['shipment_reference'] ?? $payload['shipment_reference'] ?? ''));

        $events[] = [
            'status' => $status,
            'label' => $label,
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => $location,
            'tracking_number' => $trackingNumber,
            'shipment_reference' => $shipmentReference,
            'carrier_code' => $this->carrierCode(),
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
            'adapter' => $this->capabilitySummary(),
            'terminal' => $status === 'delivered',
            'tracking_status' => $status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function referenceCancellation(array $order, array $payload): array
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

        $reason = trim((string) ($payload['reason'] ?? 'Operator cancelled the shipment booking.'));
        $events = $this->trackingEvents($order['tracking_events'] ?? []);
        $events[] = [
            'status' => 'cancelled',
            'label' => $reason !== '' ? $reason : 'Shipment booking cancelled.',
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => (string) ($order['shipping_country'] ?? 'SE'),
            'tracking_number' => $trackingNumber,
            'shipment_reference' => $shipmentReference,
            'carrier_code' => $this->carrierCode(),
        ];

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Shipment cancelled',
            'message' => 'Shipment booking was cancelled and the order can be re-booked.',
            'adapter' => $this->capabilitySummary(),
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
     * @param array<string, mixed> $json
     * @return list<array<string, mixed>>
     */
    protected function normalizeServicePoints(array $json, array $context, array $payload): array
    {
        $points = $json['service_points']
            ?? $json['servicePoints']
            ?? $json['pickup_points']
            ?? $json['points']
            ?? $json['locations']
            ?? [];

        if (!is_array($points) || $points === []) {
            return $this->referenceServicePoints($context, $payload)['service_points'];
        }

        $country = $this->country($payload['country'] ?? $context['shipping_country'] ?? $context['country'] ?? 'SE');
        $normalized = [];

        foreach ($points as $point) {
            if (!is_array($point)) {
                continue;
            }

            $normalized[] = [
                'id' => (string) ($point['id'] ?? $point['service_point_id'] ?? $point['location_id'] ?? ''),
                'label' => (string) ($point['label'] ?? $point['name'] ?? 'Service point'),
                'carrier_code' => $this->carrierCode(),
                'carrier_label' => $this->label(),
                'service_level' => (string) ($point['service_level'] ?? $point['type'] ?? 'service_point'),
                'address_line' => (string) ($point['address_line'] ?? $point['address'] ?? ''),
                'postal_code' => (string) ($point['postal_code'] ?? $point['postalCode'] ?? ''),
                'city' => (string) ($point['city'] ?? ''),
                'country' => (string) ($point['country'] ?? $country),
                'distance_meters' => (int) ($point['distance_meters'] ?? $point['distance'] ?? 0),
                'cutoff_time' => (string) ($point['cutoff_time'] ?? ''),
                'supports_locker' => (bool) ($point['supports_locker'] ?? $point['locker'] ?? false),
            ];
        }

        return $normalized !== [] ? $normalized : $this->referenceServicePoints($context, $payload)['service_points'];
    }

    /**
     * @param array<string, mixed> $json
     * @return array<string, mixed>
     */
    protected function normalizeBooking(array $order, array $payload, array $json): array
    {
        $reference = (string) ($json['shipment_reference'] ?? $json['shipmentReference'] ?? $json['reference'] ?? '');
        $tracking = (string) ($json['tracking_number'] ?? $json['trackingNumber'] ?? '');

        if ($reference === '' || $tracking === '') {
            $referenceResult = $this->referenceBooking($order, array_merge($payload, [
                'shipment_reference' => $reference,
                'tracking_number' => $tracking,
            ]));
            $attributes = is_array($referenceResult['attributes'] ?? null) ? $referenceResult['attributes'] : [];
            $reference = $reference !== '' ? $reference : (string) ($attributes['shipment_reference'] ?? '');
            $tracking = $tracking !== '' ? $tracking : (string) ($attributes['tracking_number'] ?? '');
        }

        $labelReference = (string) ($json['label_reference'] ?? $json['labelReference'] ?? 'LBL-' . $reference);
        $labelUrl = (string) ($json['label_url'] ?? $json['labelUrl'] ?? $this->labelUrl($reference, (string) ($payload['label_format'] ?? $this->setting('LABEL_FORMAT', 'pdf'))));
        $servicePointId = trim((string) ($payload['service_point_id'] ?? $json['service_point_id'] ?? $order['shipping_service_point_id'] ?? ''));
        $servicePointName = trim((string) ($payload['service_point_name'] ?? $json['service_point_name'] ?? $order['shipping_service_point_name'] ?? ''));
        $events = $this->trackingEvents($order['tracking_events'] ?? []);
        $events[] = [
            'status' => 'booked',
            'label' => sprintf('Shipment booked with %s.', $this->label()),
            'occurred_at' => gmdate(DATE_ATOM),
            'location' => $servicePointName !== '' ? $servicePointName : (string) ($order['shipping_country'] ?? 'SE'),
            'tracking_number' => $tracking,
            'shipment_reference' => $reference,
            'carrier_code' => $this->carrierCode(),
            'label_reference' => $labelReference,
            'label_url' => $labelUrl,
            'label_format' => (string) ($json['label_format'] ?? $payload['label_format'] ?? $this->setting('LABEL_FORMAT', 'pdf')),
            'provider_reference' => (string) ($json['provider_reference'] ?? $json['providerReference'] ?? ''),
        ];

        return [
            'successful' => true,
            'status' => 200,
            'title' => 'Shipment booked',
            'message' => (string) ($json['message'] ?? 'Shipment booking completed through the carrier adapter.'),
            'attributes' => [
                'shipping_carrier' => $this->carrierCode(),
                'shipping_carrier_label' => $this->label(),
                'tracking_number' => $tracking,
                'tracking_url' => (string) ($json['tracking_url'] ?? $json['trackingUrl'] ?? $this->portalUrl()),
                'shipment_reference' => $reference,
                'shipping_service_point_id' => $servicePointId,
                'shipping_service_point_name' => $servicePointName,
                'tracking_events' => json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ],
            'carrier' => $this->carrierPayload(),
            'adapter' => $this->capabilitySummary(),
            'label_reference' => $labelReference,
            'label_url' => $labelUrl,
            'label_format' => (string) ($json['label_format'] ?? $payload['label_format'] ?? $this->setting('LABEL_FORMAT', 'pdf')),
            'provider_payload' => $json,
        ];
    }

    /**
     * @param array<string, mixed> $json
     * @return array<string, mixed>
     */
    protected function normalizeTrackingUpdate(array $order, array $payload, array $json): array
    {
        return $this->referenceTrackingUpdate($order, array_merge($payload, [
            'status' => (string) ($json['status'] ?? $json['tracking_status'] ?? $payload['status'] ?? ''),
            'label' => (string) ($json['label'] ?? $json['message'] ?? $payload['label'] ?? ''),
            'location' => (string) ($json['location'] ?? $payload['location'] ?? ''),
        ])) + ['provider_payload' => $json];
    }

    /**
     * @return array<string, mixed>
     */
    protected function missingEndpoint(string $title, string $key): array
    {
        return [
            'successful' => false,
            'status' => 422,
            'title' => $title,
            'message' => sprintf('Carrier adapter [%s] is in live mode but [%s] is not configured.', $this->carrierCode(), $key),
            'adapter' => $this->capabilitySummary(),
        ];
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    protected function providerFailure(string $title, array $response): array
    {
        return [
            'successful' => false,
            'status' => (int) ($response['status'] ?? 502),
            'title' => $title,
            'message' => sprintf('Carrier adapter [%s] provider request failed.', $this->carrierCode()),
            'adapter' => $this->capabilitySummary(),
            'provider_payload' => $response['json'] ?? [],
        ];
    }

    protected function endpointUrl(string $key): string
    {
        $url = trim((string) $this->setting($key, ''));

        if ($url === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $url) === 1) {
            return $url;
        }

        $base = rtrim(trim((string) $this->setting('API_BASE', '')), '/');

        return $base !== '' ? $base . '/' . ltrim($url, '/') : '';
    }

    /**
     * @return array{status:int,headers:array<string, string>,body:string,json:array<string, mixed>}
     */
    protected function requestJson(string $method, string $url, ?array $json = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $apiKey = trim((string) $this->setting('API_KEY', ''));

        if ($apiKey !== '') {
            $scheme = trim((string) $this->setting('AUTH_SCHEME', 'Bearer'));
            $headers['Authorization'] = trim($scheme . ' ' . $apiKey);
            $headers['X-API-Key'] = $apiKey;
        }

        $body = $json === null ? '' : json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $response = function_exists('curl_init')
            ? $this->curlRequest($method, $url, $headers, $body)
            : $this->streamRequest($method, $url, $headers, $body);

        try {
            $decoded = $response['body'] !== ''
                ? json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR)
                : [];
        } catch (\JsonException) {
            $decoded = [];
        }

        $response['json'] = is_array($decoded) ? $decoded : [];

        return $response;
    }

    /**
     * @param array<string, string> $headers
     * @return array{status:int,headers:array<string, string>,body:string,json:array<string, mixed>}
     */
    private function curlRequest(string $method, string $url, array $headers, string $body): array
    {
        $handle = curl_init($url);

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Unable to initialize cURL for carrier adapter [%s].', $this->carrierCode()));
        }

        $responseHeaders = [];
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => (int) $this->setting('TIMEOUT', 30),
            CURLOPT_CONNECTTIMEOUT => (int) $this->setting('CONNECT_TIMEOUT', 10),
            CURLOPT_HTTPHEADER => $this->headerLines($headers),
            CURLOPT_HEADERFUNCTION => function ($curl, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                $header = trim($line);

                if ($header === '' || !str_contains($header, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $header, 2);
                $responseHeaders[trim($name)] = trim($value);

                return $length;
            },
        ]);

        if ($body !== '') {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($responseBody === false) {
            throw new \RuntimeException(sprintf(
                'Carrier adapter [%s] request failed: %s',
                $this->carrierCode(),
                $error !== '' ? $error : 'unknown cURL error'
            ));
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => (string) $responseBody,
            'json' => [],
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return array{status:int,headers:array<string, string>,body:string,json:array<string, mixed>}
     */
    private function streamRequest(string $method, string $url, array $headers, string $body): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $this->headerLines($headers)),
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => (int) $this->setting('TIMEOUT', 30),
            ],
        ]);
        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            throw new \RuntimeException(sprintf('Carrier adapter [%s] request failed.', $this->carrierCode()));
        }

        $status = 200;
        $responseHeaders = [];

        foreach (($http_response_header ?? []) as $index => $headerLine) {
            if ($index === 0 && preg_match('/\s(\d{3})\s/', $headerLine, $matches) === 1) {
                $status = (int) $matches[1];
                continue;
            }

            if (!str_contains($headerLine, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $headerLine, 2);
            $responseHeaders[trim($name)] = trim($value);
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => (string) $responseBody,
            'json' => [],
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return list<string>
     */
    private function headerLines(array $headers): array
    {
        $lines = [];

        foreach ($headers as $name => $value) {
            $lines[] = trim((string) $name) . ': ' . trim((string) $value);
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function responseSuccessful(array $response): bool
    {
        return in_array((int) ($response['status'] ?? 0), [200, 201, 202, 204], true);
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        $current = $this->settings;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($current)) {
                return $default;
            }

            $found = false;

            foreach ($current as $candidateKey => $candidateValue) {
                if (strcasecmp((string) $candidateKey, $segment) === 0) {
                    $current = $candidateValue;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return $default;
            }
        }

        return $current;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $values, bool $preserveCase = false): array
    {
        if (!is_array($values)) {
            return [];
        }

        $results = [];

        foreach ($values as $value) {
            $string = trim((string) $value);
            $string = $preserveCase ? $string : $this->normalizeName($string);

            if ($string === '' || in_array($string, $results, true)) {
                continue;
            }

            $results[] = $string;
        }

        return $results;
    }

    private function country(mixed $country): string
    {
        $normalized = strtoupper(trim((string) $country));

        return match ($normalized) {
            '', 'SWEDEN', 'SVERIGE', 'SWE' => 'SE',
            'DENMARK', 'DANMARK' => 'DK',
            'FINLAND', 'SUOMI' => 'FI',
            'NORWAY', 'NORGE' => 'NO',
            default => strlen($normalized) > 2 ? substr($normalized, 0, 2) : $normalized,
        };
    }

    /**
     * @param mixed $events
     * @return list<array<string, string>>
     */
    private function trackingEvents(mixed $events): array
    {
        if (is_string($events)) {
            try {
                $events = json_decode($events, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $events = [];
            }
        }

        if (!is_array($events)) {
            return [];
        }

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

    private function generateShipmentReference(string $orderNumber): string
    {
        return sprintf('SHP-%s-%s', preg_replace('/[^A-Z0-9]+/', '', strtoupper($orderNumber)) ?: 'ORDER', strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
    }

    private function generateTrackingNumber(string $shipmentReference): string
    {
        return sprintf('%s%sSE', $this->referencePrefix(), strtoupper(substr(hash('sha256', $shipmentReference), 0, 10)));
    }

    private function labelUrl(string $shipmentReference, string $labelFormat): string
    {
        $base = trim((string) $this->setting('LABEL_BASE_URL', 'https://shipments.langelermvc.test/labels'));

        if ($base === '') {
            return '';
        }

        return rtrim($base, '/') . '/' . rawurlencode($this->carrierCode()) . '/' . rawurlencode($shipmentReference) . '.' . rawurlencode($labelFormat);
    }

    private function referencePrefix(): string
    {
        return match ($this->carrierCode()) {
            'postnord' => 'PN',
            'instabox' => 'IB',
            'budbee' => 'BB',
            'bring' => 'BR',
            'dhl' => 'DHL',
            'schenker' => 'SCH',
            'earlybird' => 'EB',
            'airmee' => 'AM',
            'ups' => 'UPS',
            default => strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $this->carrierCode()) ?: 'CAR', 0, 3)),
        };
    }

    protected function normalizeName(string $value): string
    {
        return strtolower(str_replace('-', '_', trim($value)));
    }
}
