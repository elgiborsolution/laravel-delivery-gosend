<?php

namespace ESolution\GoSend\Tests\Feature;

use ESolution\GoSend\Contracts\GoSendClientInterface;
use ESolution\GoSend\Models\GoSendDelivery;
use ESolution\GoSend\Providers\GoSendServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class GoSendClientTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            GoSendServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('gosend.environment', 'staging');
        $app['config']->set('gosend.credentials.staging.client_id', 'test-client');
        $app['config']->set('gosend.credentials.staging.pass_key', 'test-key');

        include __DIR__ . '/../../database/migrations/2025_01_01_000000_create_gosend_deliveries_table.php';
        (new \CreateGosendDeliveriesTable())->up();
    }

    /** @test */
    public function it_can_create_booking_and_persist_delivery()
    {
        Http::fake([
            '*' => Http::response([
                'id'          => 170563,
                'orderNo'     => 'GK-11-170563',
                'bookingType' => 'Instant',
                'storeOrderId'=> 'AWBfromPartner',
            ], 200),
        ]);

        /** @var GoSendClientInterface $client */
        $client = $this->app->make(GoSendClientInterface::class);

        $payload = [
            'paymentType' => 3,
            'shipment_method' => 'Instant',
            'routes' => [[
                'originName' => 'A',
                'originContactName' => 'A',
                'originContactPhone' => '6281xxxxxxx',
                'originLatLong' => '-6.1,106.8',
                'originAddress' => 'Jl A',
                'destinationName' => 'B',
                'destinationContactName' => 'B',
                'destinationContactPhone' => '6281yyyyyyy',
                'destinationLatLong' => '-6.2,106.9',
                'destinationAddress' => 'Jl B',
                'item' => 'Test Item',
                'storeOrderId' => 'AWBfromPartner',
            ]],
        ];

        $delivery = $client->createBooking($payload);

        $this->assertInstanceOf(GoSendDelivery::class, $delivery);
        $this->assertDatabaseHas('gosend_deliveries', [
            'order_no' => 'GK-11-170563',
            'store_order_id' => 'AWBfromPartner',
        ]);

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Client-ID', 'test-client') &&
                $request->hasHeader('Pass-Key', 'test-key');
        });
    }
}
