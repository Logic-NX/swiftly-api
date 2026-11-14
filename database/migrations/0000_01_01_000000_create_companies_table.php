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
    Schema::create('companies', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('document_id')->nullable(); // RIF, NIT, etc.
        $table->decimal('current_exchange_rate', 10, 4)->default(36.42); // Tasa centralizada
        $table->boolean('allow_manual_prices')->default(true); // El interruptor que pediste
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
