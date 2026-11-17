<?php

namespace App\Http\Controllers;

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
        // 1. Validación básica de los datos enviados por el frontend
        $request->validate([
            'method' => 'required|string',
            'received' => 'required|numeric',
            'change' => 'required|numeric',
            'items' => 'required|array'
        ]);

        // Iniciamos una transacción: Si algo falla, se deshacen todos los cambios en la BD
        DB::beginTransaction();

        try {
            $customerId = null;

            // 2. Manejo Híbrido del Cliente (Factura Fiscal vs Consumidor Final)
            if ($request->customer_type === 'custom') {
                // updateOrCreate busca por company_id y document_id. 
                // Si lo encuentra, actualiza el nombre/dirección. Si no, lo crea.
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

            // Variables para recalcular el total en el backend por seguridad
            $totalUsd = 0;
            $exchangeRate = $request->exchange_rate ?? 36.42; 

            // 3. Crear el registro principal de la Venta
            $sale = Sale::create([
                'company_id' => $request->user()->company_id,
                'user_id' => $request->user()->id,
                'customer_id' => $customerId, // Si fue anónimo, esto será null
                'total_usd' => 0, // Lo actualizaremos en el paso 5
                'total_bs' => 0,
                'exchange_rate' => $exchangeRate,
                'payment_method' => $request->method,
                'amount_received' => $request->received,
                'change_amount' => $request->change,
                'status' => 'completed'
            ]);

            // 4. Procesar los Productos (Items) y descontar Inventario
            foreach ($request->items as $item) {
                // Buscamos el producto en BD para usar su precio real (evita hackeos desde el frontend)
                $product = Product::findOrFail($item['id']);
                
                $subtotalUsd = $product->price * $item['quantity'];
                $totalUsd += $subtotalUsd;
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price_usd' => $product->price,
                    'subtotal_usd' => $subtotalUsd,
                ]);

                // Descontar del inventario solo si es un producto físico (no manual)
                if (!$product->is_manual) {
                    // Evitamos que el stock baje de cero
                    $newStock = max(0, $product->stock - $item['quantity']);
                    $product->update(['stock' => $newStock]);
                }
            }

            // 5. Actualizar los totales reales en la venta
            $sale->update([
                'total_usd' => $totalUsd,
                'total_bs' => $totalUsd * $exchangeRate
            ]);

            // Todo salió perfecto, guardamos los cambios en la BD definitivamente
            DB::commit();

            return response()->json([
                'message' => 'Venta registrada con éxito',
                'sale_id' => $sale->id
            ], 201);

        } catch (\Exception $e) {
            // Hubo un error (ej. se cayó la BD), deshacemos todo para no dejar datos corruptos
            DB::rollBack();
            return response()->json([
                'error' => 'Error procesando la venta',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}