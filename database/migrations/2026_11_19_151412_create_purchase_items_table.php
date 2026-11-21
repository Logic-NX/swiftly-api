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
    Schema::create('purchase_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->constrained();
        $table->integer('packs_received')->default(0); // Cuántos bultos llegaron
        $table->integer('units_received')->default(0); // Cuántas unidades sueltas llegaron
        $table->decimal('pack_cost', 10, 2)->default(0); // A cómo pagaste el bulto
        $table->decimal('subtotal', 10, 2)->default(0);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
