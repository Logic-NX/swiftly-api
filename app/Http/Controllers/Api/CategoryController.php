<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // En el futuro, tomaremos el company_id del usuario autenticado.
        // Por ahora, asumimos la primera empresa para desarrollo.
        $companyId = 1;

        $categories = Category::where('company_id', $companyId)->get();

        return response()->json($categories);
    }
}