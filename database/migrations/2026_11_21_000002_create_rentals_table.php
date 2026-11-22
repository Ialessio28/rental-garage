<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the rentals table.
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            // RESTRICT: a user or garage that still has rentals cannot be deleted
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('garage_id')->constrained()->restrictOnDelete();

            // Vehicle details
            $table->enum('vehicle_type', ['bicycle', 'motorcycle', 'car', 'electric_car']);
            $table->string('plate_number', 15)->nullable();            // empty for bicycles
            $table->string('brand_model', 100);
            $table->decimal('battery_capacity', 8, 2)->nullable();     // kWh, electric cars only
            $table->decimal('current_charge', 8, 2)->nullable();       // kWh, electric garage only

            // Rental period and costs
            $table->enum('rental_type', ['daily', 'monthly']);
            $table->unsignedSmallInteger('days');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('rent_cost');
            $table->unsignedInteger('charge_cost')->default(0);
            $table->unsignedInteger('total_cost');
            $table->string('payment_proof');
            $table->timestamps();
        });
    }

    /**
     * Drop the rentals table.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
