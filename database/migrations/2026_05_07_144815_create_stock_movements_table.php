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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number', length:20)->unique();
            $table->enum('type', ['RESTOCK','SALE','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT']);
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses');
            $table->string('reference_number', length:20)->nullable();
            $table->enum('status',['DRAFT','POSTED','CANCELLED'])->default('DRAFT');
            $table->datetime('posted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
