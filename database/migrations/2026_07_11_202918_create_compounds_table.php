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
        Schema::create('compounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->text('description')->nullable();
            $table->boolean('fence_walled')->default(false);
            $table->boolean('gated')->default(false);
            $table->boolean('security_guard')->default(false);
            $table->boolean('cctv')->default(false);
            $table->boolean('street_lights')->default(false);
            $table->boolean('playground')->default(false);
            $table->integer('total_properties');
            $table->integer('total_units');
            $table->decimal('latitude')->nullable();
            $table->decimal('longitude')->nullable();
            $table->string('google_map_url')->nullable();
            $table->string('landmark');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('compounds');
    }
};
