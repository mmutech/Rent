<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compound_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->double('amount', 15, 2);
            $table->integer('bedrooms');
            $table->integer('bathrooms')->nullable();
            $table->integer('kitchens')->nullable();
            $table->integer('living_rooms')->nullable();
            $table->integer('parking_spaces')->nullable();
            $table->enum('status', ['Available', 'Reserved', 'Occupied', 'Under_Maintenance'])->default('Available');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
