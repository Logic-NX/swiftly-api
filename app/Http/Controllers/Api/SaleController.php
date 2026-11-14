<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validar que la PWA envíe los datos correctos
        $validated = $request->validate([
            'total_usd' => 'required|numeric',
            'total_bs' => 'required|numeric',
            'exchange_rate' => 'required|numeric',
            'payment_method' => 'required|string',
            'amount_received' => 'required|numeric',
            'change_amount' => 'required|numeric',
            'items' => 'required|array',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric',
            'items.*.price_usd' => 'required|numeric',
        ]);

        try {
            // Iniciamos la transacción segura
            DB::beginTransaction();

            // 2. Crear la cabecera de la Factura (Sale)
            // Nota: Por ahora quemamos el company_id 1 y user_id 2 (Cajero)
            // En el futuro esto lo sacaremos automáticamente del token de login
            $sale = Sale::create([
                'company_id' => 1,
                'user_id' => 2, 
                'total_usd' => $validated['total_usd'],
                'total_bs' => $validated['total_bs'],
                'exchange_rate' => $validated['exchange_rate'],
                'payment_method' => $validated['payment_method'],
                'amount_received' => $validated['amount_received'],
                'change_amount' => $validated['change_amount'],
                'status' => 'completed',
            ]);

            // 3. Crear el detalle de la Factura (Sale Items)
            foreach ($validated['items'] as $item) {
                $sale->items()->create([
                    // Si es un cobro manual (ej. Delivery), no tendrá product_id
                    'product_id' => $item['product_id'] ?? null, 
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price_usd' => $item['price_usd'],
                ]);

                // (Nota para después: Aquí es donde agregaremos la lógica
                // para descontar el stock de la tabla product_batches)
            }

            // Si todo salió bien, guardamos los cambios definitivamente
            DB::commit();

            return response()->json([
                'message' => 'Venta registrada con éxito',
                'sale_id' => $sale->id
            ], 201);

        } catch (\Exception $e) {
            // Si algo falla, deshacemos todo para evitar datos corruptos
            DB::rollBack();
            return response()->json([
                'error' => 'Error al procesar la venta: ' . $e->getMessage()
            ], 500);
        }
    }
}