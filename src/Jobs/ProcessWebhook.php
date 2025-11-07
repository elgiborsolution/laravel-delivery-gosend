<?php

namespace ESolution\GoSend\Jobs;

use ESolution\GoSend\Events\GoSendStatusUpdated;
use ESolution\GoSend\Models\GoSendDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        // Example payload (from GoSend docs, Example webhook callback):
        // {
        //   "@type":"booking_event",
        //   "entity_id":"GK-11-117385",
        //   "type":"DRIVER_NOT_FOUND",
        //   "event_date":1648542933000,
        //   "booking_id":"GK-11-117385",
        //   "status":"no_driver",
        //   "booking_status":"no_driver",
        //   "booking_type":"instant",
        //   "driver_name":"",
        //   "driver_phone":"",
        //   "total_distance_in_kms":1.184000015258789,
        //   "price":20000.0,
        //   "receiver_name":"",
        //   "live_tracking_url":"http://..."
        // }

        $orderNo = Arr::get($this->payload, 'booking_id')
            ?? Arr::get($this->payload, 'entity_id');

        if (! $orderNo) {
            return;
        }

        $delivery = GoSendDelivery::where('order_no', $orderNo)->first();

        if (! $delivery) {
            return;
        }

        $status = Arr::get($this->payload, 'status')
            ?? Arr::get($this->payload, 'booking_status');

        $delivery->status = $status ?? $delivery->status;
        $delivery->booking_type = Arr::get($this->payload, 'booking_type', $delivery->booking_type);
        $delivery->driver_name = Arr::get($this->payload, 'driver_name', $delivery->driver_name);
        $delivery->driver_phone = Arr::get($this->payload, 'driver_phone', $delivery->driver_phone);
        $delivery->driver_phone2 = Arr::get($this->payload, 'driver_phone2', $delivery->driver_phone2);
        $delivery->driver_phone3 = Arr::get($this->payload, 'driver_phone3', $delivery->driver_phone3);
        $delivery->receiver_name = Arr::get($this->payload, 'receiver_name', $delivery->receiver_name);
        $delivery->live_tracking_url = Arr::get($this->payload, 'live_tracking_url', $delivery->live_tracking_url);
        $delivery->total_distance_in_kms = Arr::get($this->payload, 'total_distance_in_kms', $delivery->total_distance_in_kms);
        $delivery->price = Arr::get($this->payload, 'price', $delivery->price);

        $delivery->raw_webhook = $this->payload;

        $delivery->last_event_type = Arr::get($this->payload, 'type');

        $eventDate = Arr::get($this->payload, 'event_date');
        if ($eventDate) {
            // event_date is in milliseconds since epoch
            $delivery->last_event_at = Carbon::createFromTimestampMs($eventDate);
        }

        $delivery->save();

        event(new GoSendStatusUpdated($delivery, $this->payload));
    }
}
