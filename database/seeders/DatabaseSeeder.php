<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Creamos la Empresa
        $company = Company::create([
            'name' => 'Ferretería Principal',
            'document_id' => 'J-12345678-9',
            'current_exchange_rate' => 36.42,
            'allow_manual_prices' => true,
        ]);

        // 2. Creamos los Usuarios
        User::create([
            'name' => 'Andrea (Admin)',
            'email' => 'admin@swiftly.com',
            'password' => Hash::make('12345678'),
            'company_id' => $company->id,
            'role' => 'admin',
        ]);
        
        User::create([
            'name' => 'Caja 1',
            'email' => 'caja1@swiftly.com',
            'password' => Hash::make('12345678'),
            'company_id' => $company->id,
            'role' => 'cashier',
        ]);

        // 3. Creamos las Categorías
        $catFerreteria = Category::create(['company_id' => $company->id, 'name' => 'Ferretería']);
        $catViveres = Category::create(['company_id' => $company->id, 'name' => 'Víveres']);
        $catLicores = Category::create(['company_id' => $company->id, 'name' => 'Licores']);

        // 4. Creamos los Productos y sus Lotes (Inventario real)
        
        // Producto 1: Martillo
        $p1 = Product::create([
            'company_id' => $company->id,
            'category_id' => $catFerreteria->id,
            'name' => 'Martillo de uña',
            'unit' => '16 oz',
            'price' => 8.5,
            'image_url' => 'https://images.unsplash.com/photo-1586864387967-d02ef85d93e8?w=500&q=80'
        ]);
        ProductBatch::create(['product_id' => $p1->id, 'batch_number' => 'LOTE-A1', 'stock' => 15, 'cost_usd' => 4.5]);

        // Producto 2: Arroz
        $p2 = Product::create([
            'company_id' => $company->id,
            'category_id' => $catViveres->id,
            'name' => 'Arroz 1kg',
            'unit' => '1 kg',
            'price' => 1.8,
            'image_url' => 'https://images.unsplash.com/photo-1586201375761-83865001e8ac?w=500&q=80'
        ]);
        ProductBatch::create(['product_id' => $p2->id, 'batch_number' => 'LOTE-B2', 'stock' => 100, 'cost_usd' => 1.2, 'expiration_date' => Carbon::now()->addMonths(6)]);

        // Producto 3: Cerveza
        $p3 = Product::create([
            'company_id' => $company->id,
            'category_id' => $catLicores->id,
            'name' => 'Cerveza Lager',
            'unit' => '330 ml',
            'price' => 1.2,
            'image_url' => 'https://images.unsplash.com/photo-1614316654258-00a40d510e42?w=500&q=80'
        ]);
        ProductBatch::create(['product_id' => $p3->id, 'batch_number' => 'LOTE-C3', 'stock' => 48, 'cost_usd' => 0.8, 'expiration_date' => Carbon::now()->addMonths(3)]);
    }
}