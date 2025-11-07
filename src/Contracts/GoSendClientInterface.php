<?php

namespace ESolution\GoSend\Contracts;

use ESolution\GoSend\Models\GoSendDelivery;

interface GoSendClientInterface
{
    /**
     * Clone current client with runtime config override.
     *
     * This is the main entry for multi-tenant support:
     * you can load credentials from database and pass them here.
     */
    public function withConfig(array $config): GoSendClientInterface;

    /**
     * Hit API estimate price.
     */
    public function estimatePrice(string $originLatLong, string $destinationLatLong, int $paymentType = 3): array;

    /**
     * Create booking & persist to DB.
     *
     * @param array $payload Payload according to GoSend spec.
     */
    public function createBooking(array $payload): GoSendDelivery;

    /**
     * Get booking status by orderNo (GK-xxxx).
     */
    public function getStatusByOrderNo(string $orderNo): array;

    /**
     * Get booking status by storeOrderId (internal partner ID).
     */
    public function getStatusByStoreOrderId(string $storeOrderId): array;

    /**
     * Cancel booking by orderNo.
     */
    public function cancelBooking(string $orderNo): array;
}
