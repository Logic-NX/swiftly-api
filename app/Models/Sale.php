<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = []; // Permite asignación masiva segura

    // Una Venta tiene muchos Detalles (Ítems)
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}