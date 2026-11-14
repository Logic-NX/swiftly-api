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
    Schema::create('product_batches', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->string('batch_number')->nullable(); // Ej. Lote-001
        $table->decimal('stock', 10, 3)->default(0); // Decimal por si es peso
        $table->date('manufacturing_date')->nullable(); // Fecha de fabricación (opcional)
        $table->date('expiration_date')->nullable(); // La fecha de vencimiento que pediste
        $table->decimal('cost_usd', 10, 2)->nullable(); // Cuánto te costó comprar este lote (para calcular ganancias después)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
