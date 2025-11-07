<?php

namespace ESolution\GoSend\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoSendDelivery extends Model
{
    use HasFactory;

    protected $table = 'gosend_deliveries';

    protected $fillable = [
        'store_order_id',
        'order_no',
        'booking_type',
        'status',
        'driver_name',
        'driver_phone',
        'driver_phone2',
        'driver_phone3',
        'driver_photo_url',
        'receiver_name',
        'total_distance_in_kms',
        'price',
        'live_tracking_url',
        'raw_request',
        'raw_response',
        'raw_webhook',
        'order_created_at',
        'order_dispatched_at',
        'order_arrived_at',
        'order_closed_at',
        'last_event_type',
        'last_event_at',
    ];

    protected $casts = [
        'raw_request'           => 'array',
        'raw_response'          => 'array',
        'raw_webhook'           => 'array',
        'total_distance_in_kms' => 'decimal:3',
        'order_created_at'      => 'datetime',
        'order_dispatched_at'   => 'datetime',
        'order_arrived_at'      => 'datetime',
        'order_closed_at'       => 'datetime',
        'last_event_at'         => 'datetime',
    ];
}
