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
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
        $table->string('name');
        $table->string('unit')->default('und');
        $table->decimal('price', 10, 2);
        $table->string('image_url')->nullable();
        $table->boolean('is_manual')->default(false); // Para identificar los cobros libres
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
