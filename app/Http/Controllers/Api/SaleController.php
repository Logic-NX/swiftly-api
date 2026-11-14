<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\ProductBatch; // <-- Importante agregar el modelo de Lotes
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'total_usd' => 'required|numeric',
            'total_bs' => 'required|numeric',
            'exchange_rate' => 'required|numeric',
            'payment_method' => 'required|string',
            'amount_received' => 'required|numeric',
            'change_amount' => 'required|numeric',
            'items' => 'required|array',
            'items.*.product_id' => 'nullable|integer',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric',
            'items.*.price_usd' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear la cabecera de la Factura usando los datos del cajero logueado
            $sale = Sale::create([
                'company_id' => $request->user()->company_id, // <-- Ahora es dinámico
                'user_id' => $request->user()->id,            // <-- Ahora es dinámico
                'total_usd' => $validated['total_usd'],
                'total_bs' => $validated['total_bs'],
                'exchange_rate' => $validated['exchange_rate'],
                'payment_method' => $validated['payment_method'],
                'amount_received' => $validated['amount_received'],
                'change_amount' => $validated['change_amount'],
                'status' => 'completed',
            ]);

            // 2. Crear los ítems y descontar inventario
            foreach ($validated['items'] as $item) {
                $sale->items()->create([
                    'product_id' => $item['product_id'] ?? null, 
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price_usd' => $item['price_usd'],
                ]);

                // 3. MOTOR DE DESCUENTO FIFO (Solo si es un producto real, no un cobro manual)
                if (!empty($item['product_id'])) {
                    $qtyToDeduct = $item['quantity'];

                    // Buscamos los lotes de este producto que tengan stock, del más viejo al más nuevo
                    $batches = ProductBatch::where('product_id', $item['product_id'])
                        ->where('stock', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate() // Evita que 2 cajeros vendan el mismo martillo exacto al mismo tiempo
                        ->get();

                    foreach ($batches as $batch) {
                        if ($qtyToDeduct <= 0) break; // Si ya descontamos todo, salimos del ciclo

                        if ($batch->stock >= $qtyToDeduct) {
                            // El lote tiene suficiente para cubrir todo
                            $batch->decrement('stock', $qtyToDeduct);
                            $qtyToDeduct = 0;
                        } else {
                            // El lote no alcanza, lo vaciamos y seguimos con el siguiente
                            $qtyToDeduct -= $batch->stock;
                            $batch->update(['stock' => 0]);
                        }
                    }

                    // Si al final del ciclo todavía falta por descontar, es porque vendimos sin inventario
                    if ($qtyToDeduct > 0) {
                        throw new \Exception("Stock insuficiente para: " . $item['product_name']);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Venta registrada con éxito',
                'sale_id' => $sale->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage() // Ahora devuelve el error exacto (ej. "Stock insuficiente")
            ], 500);
        }
    }
}