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
    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained(); // Cajero que procesó
        $table->decimal('total_usd', 10, 2);
        $table->decimal('total_bs', 12, 2);
        $table->decimal('exchange_rate', 10, 4);
        $table->string('payment_method'); // pago_movil, usd, bs, punto
        $table->decimal('amount_received', 12, 2);
        $table->decimal('change_amount', 12, 2)->default(0);
        $table->enum('status', ['completed', 'pending', 'cancelled'])->default('completed');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
