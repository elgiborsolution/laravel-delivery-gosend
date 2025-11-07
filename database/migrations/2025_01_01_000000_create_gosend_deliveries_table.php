<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGosendDeliveriesTable extends Migration
{
    public function up(): void
    {
        Schema::create('gosend_deliveries', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Partner order id & GoSend order no
            $table->string('store_order_id')->nullable()->unique();
            $table->string('order_no')->nullable()->index();

            // Type & status
            $table->string('booking_type')->nullable(); // instant, same_day, etc.
            $table->string('status')->nullable();       // delivered, cancelled, etc.

            // Driver info
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->string('driver_phone2')->nullable();
            $table->string('driver_phone3')->nullable();
            $table->string('driver_photo_url')->nullable();

            // Receiver info
            $table->string('receiver_name')->nullable();

            // Price & distance
            $table->decimal('total_distance_in_kms', 8, 3)->nullable();
            $table->unsignedBigInteger('price')->nullable();

            // Live tracking URL
            $table->string('live_tracking_url')->nullable();

            // Raw payloads
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('raw_webhook')->nullable();

            // Timestamps from GoSend, if you want to store them
            $table->timestamp('order_created_at')->nullable();
            $table->timestamp('order_dispatched_at')->nullable();
            $table->timestamp('order_arrived_at')->nullable();
            $table->timestamp('order_closed_at')->nullable();

            // Last webhook event
            $table->string('last_event_type')->nullable();
            $table->timestamp('last_event_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gosend_deliveries');
    }
}
