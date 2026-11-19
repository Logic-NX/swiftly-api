<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('units_per_pack')->default(1)->after('unit');
            $table->decimal('pack_cost', 10, 2)->nullable()->after('price'); // Opcional, costo por bulto al comprar
            $table->decimal('pack_price', 10, 2)->nullable()->after('pack_cost'); // Opcional, precio de venta del bulto
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['units_per_pack', 'pack_cost', 'pack_price']);
        });
    }
};