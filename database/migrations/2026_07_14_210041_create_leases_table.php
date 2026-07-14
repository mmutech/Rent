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
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique()->index();
            $table->foreignId('booking_id');
            $table->string('lease_type')->default('Rental');
            $table->boolean('signed_by_tenant')->default(false);
            $table->boolean('signed_by_agent')->default(false);
            $table->boolean('signed_by_landlord')->default(false);
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['Draft', 'Completed', 'Terminated', 'Expired'])->default('Draft');
            $table->string('payment_frequency')->default('Monthly');
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
        Schema::dropIfExists('leases');
    }
};
