<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        // 1. Aseguramos el company_id (Si es null, usamos el 1 por defecto)
        $companyId = $request->user()->company_id ?? 1;
        
        // 2. Forzamos la zona horaria a la de Venezuela para evitar desfases de días
        $timezone = 'America/Caracas';
        $today = Carbon::now($timezone)->format('Y-m-d');

        // --- MÉTRICAS PRINCIPALES ---
        // Usamos DATE(created_at) a nivel de base de datos para asegurar el match
        $todaySalesQuery = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereDate('created_at', $today);

        $totalUsd = $todaySalesQuery->sum('total_usd');
        $totalBs = $todaySalesQuery->sum('total_bs');
        $transactions = $todaySalesQuery->count();

        // --- LISTA: POR AGOTARSE ---
        $allProducts = Product::where('company_id', $companyId)->get();
        $lowStockItems = [];
        foreach ($allProducts as $p) {
            // Sumamos el stock de todos sus lotes
            $stock = $p->batches()->sum('stock');
            if ($stock <= 5) {
                $lowStockItems[] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'stock' => (int)$stock,
                    'img' => $p->image_url ?? 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=100&q=80'
                ];
            }
        }
        // Tomamos solo los 5 más críticos para el dashboard
        $lowStockItems = array_slice($lowStockItems, 0, 5);
        $lowStockCount = count($lowStockItems);

        // --- GRÁFICO: ÚLTIMOS 7 DÍAS ---
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now($timezone)->subDays($i);
            $dailySales = Sale::where('company_id', $companyId)
                ->where('status', 'completed')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->sum('total_usd');
            
            $chartData[] = [
                'day' => $date->isoFormat('dddd'), 
                'short' => $date->format('D'), // Ej: 'Mon', 'Tue'
                'total' => round($dailySales, 2)
            ];
        }

      // --- NOVEDADES (Con filtro preparativo) ---
        // 1. Obtenemos las últimas 4 ventas reales
        $salesActivities = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get()
            ->map(function($sale) use ($timezone) {
                return [
                    'id' => 'sale_'.$sale->id,
                    'type' => 'sale', // <-- Etiqueta para el filtro
                    'title' => 'Venta en POS (#'.$sale->id.')',
                    'desc' => 'Cobro de $'.number_format($sale->total_usd, 2),
                    'time' => Carbon::parse($sale->created_at)->setTimezone($timezone)->diffForHumans(),
                    'timestamp' => Carbon::parse($sale->created_at)->timestamp
                ];
            });

        // 2. Simulamos 2 ingresos de mercancía (Hasta que hagamos el módulo de compras)
        $inventoryActivities = collect([
            [
                'id' => 'inv_1',
                'type' => 'inventory', // <-- Etiqueta para el filtro
                'title' => 'Ingreso de Mercancía',
                'desc' => 'Factura Compra #00142 (Prov. Polar)',
                'time' => 'hace 2 horas',
                'timestamp' => Carbon::now($timezone)->subHours(2)->timestamp
            ],
            [
                'id' => 'inv_2',
                'type' => 'inventory',
                'title' => 'Ajuste de Inventario',
                'desc' => '+3 Martillo de uña (Auditoría)',
                'time' => 'hace 5 horas',
                'timestamp' => Carbon::now($timezone)->subHours(5)->timestamp
            ]
        ]);

        // 3. Unimos ambas listas y las ordenamos por fecha
        $activities = $salesActivities->concat($inventoryActivities)
            ->sortByDesc('timestamp')
            ->take(6)
            ->values()
            ->all();

        return response()->json([
            'sales_usd' => round($totalUsd, 2),
            'sales_bs' => round($totalBs, 2),
            'transactions' => $transactions,
            'low_stock_alerts' => $lowStockCount,
            'low_stock_items' => $lowStockItems,
            'chart_data' => $chartData,
            'activities' => $activities
        ]);
    }
}