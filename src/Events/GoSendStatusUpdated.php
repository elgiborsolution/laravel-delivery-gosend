<?php

namespace ESolution\GoSend\Events;

use ESolution\GoSend\Models\GoSendDelivery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoSendStatusUpdated
{
    use Dispatchable, SerializesModels;

    public GoSendDelivery $delivery;
    public array $payload;

    public function __construct(GoSendDelivery $delivery, array $payload)
    {
        $this->delivery = $delivery;
        $this->payload = $payload;
    }
}
