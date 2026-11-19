<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Sugerencia: Usar el company_id del usuario autenticado si es posible
        $companyId = $request->user()->company_id ?? 1;

        // Traemos los productos, su categoría y todos sus lotes de inventario
        $products = Product::with(['category:id,name', 'batches'])
            ->where('company_id', $companyId)
            ->where('is_manual', false) // No enviamos los manuales al catálogo
            ->get()
            ->map(function ($product) {
                // Sumamos el stock real de todos sus lotes (FIFO)
                $product->stock = (int) $product->batches->sum('stock');
                
                // Limpiamos los lotes del JSON para ahorrar datos y memoria en el teléfono
                unset($product->batches);
                
                return $product;
            });

        return response()->json($products);
    }
}