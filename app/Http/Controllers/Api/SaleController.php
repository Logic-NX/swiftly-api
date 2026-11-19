<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validación exacta y alineada con el frontend de Vue
        $request->validate([
            'method' => 'required|string',
            'received' => 'required|numeric|min:0',
            'change' => 'required|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|gt:0',
            
            // Aceptamos explícitamente "final" o "custom"
            'customer_type' => 'required|string|in:final,custom',
            
            // Obligatorios solo si es custom, pero permitimos nulos si es final
            'customer_doc' => 'required_if:customer_type,custom|nullable|string',
            'customer_name' => 'required_if:customer_type,custom|nullable|string',
            'customer_address' => 'nullable|string',
            
            // Validación de items simple y directa
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable', 
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price_usd' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $customerId = null;

            // 2. Gestión de Cliente (Solo si es Factura Fiscal)
            if ($request->customer_type === 'custom') {
                $customer = Customer::updateOrCreate(
                    [
                        'company_id' => $request->user()->company_id,
                        'document_id' => $request->customer_doc,
                    ],
                    [
                        'name' => $request->customer_name,
                        'address' => $request->customer_address,
                    ]
                );
                $customerId = $customer->id;
            }

            $totalUsd = 0;
            $exchangeRate = $request->exchange_rate ?? 36.42; 

            // 3. Crear Venta
            $sale = Sale::create([
                'company_id' => $request->user()->company_id,
                'user_id' => $request->user()->id,
                'customer_id' => $customerId,
                'total_usd' => 0, 
                'total_bs' => 0,
                'exchange_rate' => $exchangeRate,
                'payment_method' => $request->method,
                'amount_received' => $request->received,
                'change_amount' => $request->change,
                'status' => 'completed'
            ]);

            // 4. Procesar Items y Lógica FIFO
            foreach ($request->items as $item) {
                $product = null;
                $productId = $item['id'] ?? null;
                $priceUsd = $item['price_usd']; // Tomamos el precio del payload por defecto

                if ($productId) {
                    $product = Product::findOrFail($productId);
                    $priceUsd = $product->price; // Sobrescribimos con el precio real por seguridad
                    
                    // Solo descontamos si NO es un producto manual
                    if (!$product->is_manual) {
                        $qtyToDeduct = $item['quantity'];
                        
                        // Buscamos los lotes disponibles (FIFO)
                        $batches = $product->batches()
                            ->where('stock', '>', 0)
                            ->orderBy('created_at', 'asc')
                            ->lockForUpdate()
                            ->get();

                        foreach ($batches as $batch) {
                            if ($qtyToDeduct <= 0) break; 

                            if ($batch->stock >= $qtyToDeduct) {
                                $batch->stock -= $qtyToDeduct;
                                $batch->save();
                                $qtyToDeduct = 0;
                            } else {
                                $qtyToDeduct -= $batch->stock;
                                $batch->stock = 0;
                                $batch->save();
                            }
                        }
                    }
                }

                $subtotalUsd = $priceUsd * $item['quantity'];
                $totalUsd += $subtotalUsd;
                
                // 5. Guardar el detalle de la venta
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price_usd' => $priceUsd,
                ]);
            }

            // 6. Actualizar totales finales
            $sale->update([
                'total_usd' => $totalUsd,
                'total_bs' => $totalUsd * $exchangeRate
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Venta registrada con éxito',
                'sale_id' => $sale->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error procesando la venta',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}