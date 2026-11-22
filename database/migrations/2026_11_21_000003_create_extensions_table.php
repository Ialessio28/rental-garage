<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the extensions table (history of rental extensions done by the admin).
     */
    public function up(): void
    {
        Schema::create('extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->restrictOnDelete();
            $table->date('old_end_date');
            $table->date('new_end_date');
            $table->unsignedInteger('extra_cost');
            $table->string('payment_proof');
            $table->timestamps();
        });
    }

    /**
     * Drop the extensions table.
     */
    public function down(): void
    {
        Schema::dropIfExists('extensions');
    }
};
