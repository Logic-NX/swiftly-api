<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'invoice_number' => 'nullable|string',
            'supplier' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.packs_received' => 'required|integer|min:0',
            'items.*.units_received' => 'required|integer|min:0',
            'items.*.pack_cost' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear la Compra (Cabecera)
            $purchase = Purchase::create([
                'invoice_number' => $request->invoice_number,
                'supplier' => $request->supplier,
                'notes' => $request->notes,
                'total_amount' => 0 // Lo calcularemos ahora
            ]);

            $totalPurchase = 0;

            // 2. Procesar cada producto recibido
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Calculamos el subtotal de esa línea (Costo del bulto * cantidad de bultos)
                // Si también meten unidades sueltas, sumamos la proporción
                $unitCost = $product->units_per_pack > 0 ? ($item['pack_cost'] / $product->units_per_pack) : $item['pack_cost'];
                $subtotal = ($item['packs_received'] * $item['pack_cost']) + ($item['units_received'] * $unitCost);
                $totalPurchase += $subtotal;

                // Guardar el detalle de la factura
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'packs_received' => $item['packs_received'],
                    'units_received' => $item['units_received'],
                    'pack_cost' => $item['pack_cost'],
                    'subtotal' => $subtotal,
                ]);

                // 3. LA MAGIA: Actualizar el inventario del producto
                $totalUnitsToAdd = ($item['packs_received'] * $product->units_per_pack) + $item['units_received'];
                
                $product->stock += $totalUnitsToAdd;
                $product->pack_cost = $item['pack_cost']; // Actualizamos el último costo
                $product->save();
            }

            // Actualizamos el total de la factura
            $purchase->update(['total_amount' => $totalPurchase]);

            DB::commit();

            return response()->json([
                'message' => 'Recepción de stock procesada con éxito.',
                'purchase' => $purchase
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al procesar la compra: ' . $e->getMessage()], 500);
        }
    }
}