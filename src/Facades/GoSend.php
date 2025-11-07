<?php

namespace ESolution\GoSend\Facades;

use Illuminate\Support\Facades\Facade;
use ESolution\GoSend\Contracts\GoSendClientInterface;

/**
 * @method static \ESolution\GoSend\Contracts\GoSendClientInterface withConfig(array $config)
 * @method static array estimatePrice(string $originLatLong, string $destinationLatLong, int $paymentType = 3)
 * @method static \ESolution\GoSend\Models\GoSendDelivery createBooking(array $payload)
 * @method static array getStatusByOrderNo(string $orderNo)
 * @method static array getStatusByStoreOrderId(string $storeOrderId)
 * @method static array cancelBooking(string $orderNo)
 */
class GoSend extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GoSendClientInterface::class;
    }
}
