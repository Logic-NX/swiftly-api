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
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        $table->string('document_id')->index(); // V-12345678, J-123456789 (Cédula o RIF)
        $table->string('name');
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->text('address')->nullable(); // Obligatorio para facturas fiscales completas
        $table->timestamps();

        // Evitar clientes duplicados en la misma empresa
        $table->unique(['company_id', 'document_id']); 
    });
}
    /**
     * Reverse the migrations.
     */ 
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
