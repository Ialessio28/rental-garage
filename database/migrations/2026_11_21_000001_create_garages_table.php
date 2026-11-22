<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the garages table.
     */
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique();                       // S001, C001, E001
            $table->enum('type', ['bicycle', 'car', 'electric']);
            $table->unsignedInteger('daily_price');
            $table->unsignedInteger('kwh_price')->nullable();          // electric garages only
            $table->timestamps();
        });
    }

    /**
     * Drop the garages table.
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
