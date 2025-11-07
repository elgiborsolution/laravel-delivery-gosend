<?php

namespace ESolution\GoSend\Services;

use ESolution\GoSend\Contracts\GoSendClientInterface;
use ESolution\GoSend\Exceptions\GoSendException;
use ESolution\GoSend\Models\GoSendDelivery;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class GoSendHttpClient implements GoSendClientInterface
{
    protected HttpFactory $http;

    /**
     * Base config from config/gosend.php
     */
    protected array $config;

    public function __construct(HttpFactory $httpClient, array $config)
    {
        $this->http = $httpClient;
        $this->config = $config;
    }

    public function withConfig(array $config): GoSendClientInterface
    {
        $merged = array_replace_recursive($this->config, $config);

        return new self($this->http, $merged);
    }

    protected function baseUrl(): string
    {
        $env = $this->config['environment'] ?? 'staging';

        return $this->config['base_urls'][$env] ?? $this->config['base_urls']['staging'];
    }

    protected function credentials(): array
    {
        $env = $this->config['environment'] ?? 'staging';

        return $this->config['credentials'][$env] ?? [];
    }

    protected function request(string $method, string $uri, array $options = []): array
    {
        $timeout = $this->config['http']['timeout'] ?? 10;
        $retries = (int) ($this->config['http']['retries'] ?? 2);
        $retrySleep = (int) ($this->config['http']['retry_sleep_ms'] ?? 250);

        $creds = $this->credentials();

        $headers = array_merge([
            'Client-ID'    => $creds['client_id'] ?? '',
            'Pass-Key'     => $creds['pass_key'] ?? '',
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ], $options['headers'] ?? []);

        $url = rtrim($this->baseUrl(), '/') . '/' . ltrim($uri, '/');

        // For GET requests we pass query string, for others JSON body.
        $http = $this->http
            ->timeout($timeout)
            ->withHeaders($headers)
            ->retry($retries, $retrySleep);

        if (strtolower($method) === 'get') {
            $response = $http->get($url, $options['query'] ?? []);
        } else {
            $response = $http->{$method}($url, $options['body'] ?? []);
        }

        if ($response->failed()) {
            throw new GoSendException(
                sprintf('GoSend API error (%s %s): %s', strtoupper($method), $uri, $response->body()),
                $response->status()
            );
        }

        return $response->json();
    }

    public function estimatePrice(string $originLatLong, string $destinationLatLong, int $paymentType = 3): array
    {
        // Spec: GET /gokilat/v10/calculate/price?origin=<origin latlong>&destination=<destination latlong>&paymentType=3
        $uri = '/gokilat/v10/calculate/price';

        return $this->request('get', $uri, [
            'query' => [
                'origin'      => $originLatLong,
                'destination' => $destinationLatLong,
                'paymentType' => $paymentType,
            ],
        ]);
    }

    public function createBooking(array $payload): GoSendDelivery
    {
        // Spec: POST /gokilat/v10/booking
        $uri = '/gokilat/v10/booking';

        $response = $this->request('post', $uri, [
            'body' => $payload,
        ]);

        // Example response from docs:
        // { "id": 170563, "orderNo": "GK-11-170563", "bookingType": "Instant", "storeOrderId": "AWBfromPartner", ... }
        $orderNo = Arr::get($response, 'orderNo');
        $bookingType = Arr::get($response, 'bookingType');
        $storeOrderId = Arr::get($response, 'storeOrderId');

        $delivery = GoSendDelivery::query()->firstOrNew([
            'store_order_id' => $storeOrderId,
        ]);

        $delivery->fill([
            'order_no'     => $orderNo,
            'booking_type' => $bookingType,
            'status'       => null,
            'raw_request'  => $payload,
            'raw_response' => $response,
        ]);

        $delivery->save();

        return $delivery;
    }

    public function getStatusByOrderNo(string $orderNo): array
    {
        // Spec: GET /gokilat/v10/booking/orderno/<orderNo>
        $uri = '/gokilat/v10/booking/orderno/' . urlencode($orderNo);

        $result = $this->request('get', $uri);

        $this->updateLocalStatus($result);

        return $result;
    }

    public function getStatusByStoreOrderId(string $storeOrderId): array
    {
        // Spec: GET /gokilat/v10/booking/storeOrderId/<storeOrderId>
        $uri = '/gokilat/v10/booking/storeOrderId/' . urlencode($storeOrderId);

        $result = $this->request('get', $uri);

        $this->updateLocalStatus($result);

        return $result;
    }

    public function cancelBooking(string $orderNo): array
    {
        // Spec: PUT /gokilat/v10/booking/cancel
        $uri = '/gokilat/v10/booking/cancel';

        $body = [
            // In docs: bookingId deprecated, use orderNo (GK-xxxx)
            'orderNo' => $orderNo,
        ];

        $result = $this->request('put', $uri, [
            'body' => $body,
        ]);

        $delivery = GoSendDelivery::where('order_no', $orderNo)->first();
        if ($delivery) {
            $delivery->status = 'cancelled';
            $delivery->raw_response = array_merge($delivery->raw_response ?? [], ['cancel' => $result]);
            $delivery->save();
        }

        return $result;
    }

    protected function updateLocalStatus(array $statusPayload): void
    {
        $orderNo = Arr::get($statusPayload, 'orderNo');

        if (! $orderNo) {
            return;
        }

        $delivery = GoSendDelivery::where('order_no', $orderNo)->first();

        if (! $delivery) {
            return;
        }

        // Bookings status spec: use "status" field, other fields deprecated.
        $delivery->status = Arr::get($statusPayload, 'status', $delivery->status);
        $delivery->booking_type = Arr::get($statusPayload, 'bookingType', $delivery->booking_type);
        $delivery->driver_name = Arr::get($statusPayload, 'driverName', $delivery->driver_name);
        $delivery->driver_phone = Arr::get($statusPayload, 'driverPhone', $delivery->driver_phone);
        $delivery->receiver_name = Arr::get($statusPayload, 'receiverName', $delivery->receiver_name);
        $delivery->price = Arr::get($statusPayload, 'totalPrice', $delivery->price);
        $delivery->live_tracking_url = Arr::get($statusPayload, 'liveTrackingUrl', $delivery->live_tracking_url);
        $delivery->total_distance_in_kms = Arr::get($statusPayload, 'totalDistanceInKms', $delivery->total_distance_in_kms);

        $delivery->order_created_at = $this->parseTime(Arr::get($statusPayload, 'orderCreatedTime'));
        $delivery->order_dispatched_at = $this->parseTime(Arr::get($statusPayload, 'orderDispatchTime'));
        $delivery->order_arrived_at = $this->parseTime(Arr::get($statusPayload, 'orderArrivalTime'));
        $delivery->order_closed_at = $this->parseTime(Arr::get($statusPayload, 'orderClosedTime'));

        $delivery->save();
    }

    protected function parseTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value);
    }
}
