# Laravel Delivery GoSend

[![PHP Version](https://img.shields.io/badge/PHP-%5E8.2-blue)](#)
[![Laravel Version](https://img.shields.io/badge/Laravel-%5E10.0-orange)](#)
[![License](https://img.shields.io/badge/License-Apache--2.0-green)](#)

Integration package for **GoSend** delivery API (Instant & Same Day) for Laravel 10+, designed to support **multi-tenant** usage via **runtime config injection**.

> **Note**: This library is not an official GoSend SDK. It is a community package built around the integration guide.

---

## Features

- Create GoSend bookings (Instant / Same Day / InstantCar).
- Retrieve booking status (by `orderNo` or `storeOrderId`).
- Cancel bookings.
- Price & distance estimation.
- Webhook endpoint to receive driver / booking status updates.
- `GoSendDelivery` model to store bookings.
- Event `GoSendStatusUpdated` dispatched on every webhook update.
- Facade `GoSend` for quick usage.
- Config-driven & extendable architecture.
- Multi-tenant ready by overriding config at runtime (`withConfig()`).

---

## Requirements

- PHP `^8.2`
- Laravel `^10.0`
- ext-json
- `guzzlehttp/guzzle` `^7.0|^8.0`

---

## Installation

```bash
composer require elgibor-solution/laravel-delivery-gosend
```

The service provider and facade will be auto-discovered.

### Publish Config & Migrations

```bash
php artisan vendor:publish --provider="ESolution\GoSend\Providers\GoSendServiceProvider" --tag=gosend-config
php artisan vendor:publish --provider="ESolution\GoSend\Providers\GoSendServiceProvider" --tag=gosend-migrations
```

Run migrations:

```bash
php artisan migrate
```

---

## Configuration

Main config file: `config/gosend.php`.

### Environment & Base URLs

```php
'environment' => env('GOSEND_ENV', 'staging'),

'base_urls' => [
    'staging' => env('GOSEND_STAGING_URL', 'https://integration-kilat-api.gojekapi.com'),
    'production' => env('GOSEND_PRODUCTION_URL', 'https://kilat-api.gojekapi.com'),
],
```

Set in `.env`:

```env
GOSEND_ENV=staging

GOSEND_STAGING_CLIENT_ID=your-staging-client-id
GOSEND_STAGING_PASS_KEY=your-staging-pass-key

GOSEND_PRODUCTION_CLIENT_ID=your-prod-client-id
GOSEND_PRODUCTION_PASS_KEY=your-prod-pass-key
```

### HTTP Options

```php
'http' => [
    'timeout' => env('GOSEND_HTTP_TIMEOUT', 10),
    'retries' => env('GOSEND_HTTP_RETRIES', 2),
    'retry_sleep_ms' => env('GOSEND_HTTP_RETRY_SLEEP_MS', 250),
],
```

### Routes & Webhook

```php
'routes' => [
    'enabled' => env('GOSEND_ROUTES_ENABLED', true),
    'prefix' => env('GOSEND_ROUTE_PREFIX', 'gosend'),
    'middleware' => ['api'],
],

'webhook' => [
    'token_header' => env('GOSEND_WEBHOOK_TOKEN_HEADER', 'X-Callback-Token'),
    'token' => env('GOSEND_WEBHOOK_TOKEN'),
    'route_name' => 'gosend.webhook',
],
```

In `.env`:

```env
GOSEND_WEBHOOK_TOKEN=some-random-secret
```

You must register this token & webhook URL with GoSend.

---

## Multi-tenant Usage (Runtime Config)

The package is **config-driven**, but also allows runtime overrides via `withConfig()` on the client or facade.

Typical pattern:

1. Store GoSend configuration (environment, client ID, pass key, etc.) **per tenant** in your database.
2. When handling a request for a specific tenant, load that row.
3. Call `withConfig()` and then use the client as usual.

Example:

```php
use GoSend;
use ESolution\GoSend\Contracts\GoSendClientInterface;

// Load from DB (pseudo code)
$tenantConfig = [
    'environment' => $store->gosend_environment, // "staging" or "production"
    'credentials' => [
        $store->gosend_environment => [
            'client_id' => $store->gosend_client_id,
            'pass_key'  => $store->gosend_pass_key,
        ],
    ],
];

// Either via facade:
$client = GoSend::withConfig($tenantConfig);

// Or via dependency injection:
$baseClient = app(GoSendClientInterface::class);
$client = $baseClient->withConfig($tenantConfig);

// Now use $client for this tenant.
```

---

## Usage

### 1. Creating a Booking

Using **Facade**:

```php
use GoSend;

$payload = [
    'paymentType' => 3,
    'shipment_method' => 'Instant',
    'routes' => [[
        'originName'             => 'Pak Andri',
        'originNote'             => 'Tunggu di lobby',
        'originContactName'      => 'The Kingdom Shop',
        'originContactPhone'     => '6285201311802',
        'originLatLong'          => '-6.1263348,106.7890888',
        'originAddress'          => 'Alamat lengkap asal',
        'destinationName'        => 'Pak Nando',
        'destinationNote'        => 'Tolong hati-hati',
        'destinationContactName' => 'Toko Jaya Agung',
        'destinationContactPhone'=> '6281254564161',
        'destinationLatLong'     => '-6.284508001748839,106.8295789',
        'destinationAddress'     => 'Alamat lengkap tujuan',
        'item'                   => 'Sepatu, Sendal, Kaos Kaki',
        'storeOrderId'           => 'AWB-123456',
        'insuranceDetails'       => [
            'applied'             => 'false',
            'fee'                 => '0',
            'product_description' => 'Sepatu, Sendal, Kaos Kaki',
            'product_price'       => '500.000',
        ],
    ]],
];

$delivery = GoSend::createBooking($payload);

// $delivery is ESolution\GoSend\Models\GoSendDelivery
```

Using **dependency injection** with multi-tenant config:

```php
use ESolution\GoSend\Contracts\GoSendClientInterface;

public function createForTenant(GoSendClientInterface $baseClient)
{
    $store = // ... load store/tenant model

    $client = $baseClient->withConfig([
        'environment' => $store->gosend_environment,
        'credentials' => [
            $store->gosend_environment => [
                'client_id' => $store->gosend_client_id,
                'pass_key'  => $store->gosend_pass_key,
            ],
        ],
    ]);

    $delivery = $client->createBooking($payload);
}
```

### 2. Get Status

```php
$status = GoSend::getStatusByOrderNo('GK-11-170563');

$delivery = \ESolution\GoSend\Models\GoSendDelivery::where('order_no', 'GK-11-170563')->first();
```

Or by `storeOrderId`:

```php
$status = GoSend::getStatusByStoreOrderId('AWB-123456');
```

### 3. Cancel Booking

```php
GoSend::cancelBooking('GK-11-170563');
```

### 4. Estimate Price

```php
$estimate = GoSend::estimatePrice(
    originLatLong: '-6.1263348,106.7890888',
    destinationLatLong: '-6.284508001748839,106.8295789',
    paymentType: 3
);

// $estimate['Instant']['price']['total_price'] ...
```

### 5. Webhook Flow (End-to-End)

- Expose webhook URL (e.g. `https://your-app.com/gosend/webhook`).
- Register this URL & token with GoSend.
- When booking status changes, GoSend will call this endpoint with a payload like:

```json
{
  "@type": "booking_event",
  "entity_id": "GK-11-117385",
  "type": "DRIVER_NOT_FOUND",
  "event_date": 1648542933000,
  "event_id": "7444acc4-0f14-419d-806c-6e245607c274",
  "partner_id": "601",
  "destination_type": "market_place",
  "booking_id": "GK-11-117385",
  "status": "no_driver",
  "booking_status": "no_driver",
  "booking_type": "instant",
  "driver_name": "",
  "driver_phone": "",
  "total_distance_in_kms": 1.184000015258789,
  "price": 20000.0,
  "receiver_name": "",
  "live_tracking_url": "http://..."
}
```

- `ProcessWebhook` job updates the `GoSendDelivery` record.
- Event `GoSendStatusUpdated` is dispatched.

Example listener in your app:

```php
namespace App\Listeners;

use ESolution\GoSend\Events\GoSendStatusUpdated;
use Illuminate\Support\Facades\Log;

class LogGoSendStatus
{
    public function handle(GoSendStatusUpdated $event): void
    {
        Log::info('GoSend status updated', [
            'order_no' => $event->delivery->order_no,
            'status'   => $event->delivery->status,
            'event'    => $event->payload['type'] ?? null,
        ]);
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    \ESolution\GoSend\Events\GoSendStatusUpdated::class => [
        \App\Listeners\LogGoSendStatus::class,
    ],
];
```

---

## Example Integration: Controller & Job

### Controller

```php
namespace App\Http\Controllers;

use ESolution\GoSend\Contracts\GoSendClientInterface;
use Illuminate\Http\Request;

class OrderDeliveryController extends Controller
{
    public function createForOrder(Request $request, GoSendClientInterface $baseClient)
    {
        $order = /* find your order */;

        $store = $order->store; // example relation

        $client = $baseClient->withConfig([
            'environment' => $store->gosend_environment,
            'credentials' => [
                $store->gosend_environment => [
                    'client_id' => $store->gosend_client_id,
                    'pass_key'  => $store->gosend_pass_key,
                ],
            ],
        ]);

        $payload = [
            'paymentType' => 3,
            'shipment_method' => 'Instant',
            'routes' => [[
                'originName'             => $order->seller_name,
                'originContactName'      => $order->seller_contact_name,
                'originContactPhone'     => $order->seller_phone,
                'originLatLong'          => $order->seller_latlong,
                'originAddress'          => $order->seller_address,
                'destinationName'        => $order->buyer_name,
                'destinationContactName' => $order->buyer_contact_name,
                'destinationContactPhone'=> $order->buyer_phone,
                'destinationLatLong'     => $order->buyer_latlong,
                'destinationAddress'     => $order->buyer_address,
                'item'                   => $order->item_summary,
                'storeOrderId'           => $order->awb_code,
            ]],
        ];

        $delivery = $client->createBooking($payload);

        $order->gosend_delivery_id = $delivery->id;
        $order->save();

        return response()->json([
            'message' => 'GoSend delivery created',
            'order_no' => $delivery->order_no,
        ]);
    }
}
```

### Job: Refresh Status

```php
namespace App\Jobs;

use ESolution\GoSend\Contracts\GoSendClientInterface;
use ESolution\GoSend\Models\GoSendDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshGoSendStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public GoSendDelivery $delivery;

    public function __construct(GoSendDelivery $delivery)
    {
        $this->delivery = $delivery;
    }

    public function handle(GoSendClientInterface $client): void
    {
        if (! $this->delivery->order_no) {
            return;
        }

        $client->getStatusByOrderNo($this->delivery->order_no);
    }
}
```

---

## Testing

Install dev dependencies and run tests:

```bash
composer install
composer test
```

This will run the provided Pest tests using `orchestra/testbench`.

---

## Changelog

All notable changes to this project will be documented here.

- `v0.1.0` – Initial release (GoSend booking, status, cancel, estimate, webhook, event, runtime multi-tenant config support).

---

## License

This package is open-sourced software licensed under the **Apache-2.0** license.

---

## Sponsor / Donation (Optional)

If this package saves you development time, you can support the maintainer:

- {{your-donation-link-here}}

Thank you! 🙏
