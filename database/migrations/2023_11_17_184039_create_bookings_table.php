<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('bookingId');
            $table->integer('bookingSlot')->default(0);
            $table->string('customerId', 100)->unique();
            $table->date('bookingFromDate')->nullable();
            $table->date('bookingEndDate')->nullable();
            $table->timestamps();
        });
    }
 
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};