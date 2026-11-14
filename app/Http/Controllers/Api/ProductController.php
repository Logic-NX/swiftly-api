<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $companyId = 1;

        // Traemos los productos de la empresa, y si tienen categoría, también su nombre
        $products = Product::with('category:id,name')
            ->where('company_id', $companyId)
            ->where('is_manual', false) // No enviamos los manuales al catálogo principal
            ->get();

        return response()->json($products);
    }
}