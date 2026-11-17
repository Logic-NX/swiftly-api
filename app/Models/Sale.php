<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = []; // Permite asignación masiva segura

    // Una Venta tiene muchos Detalles (items)
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Una Venta pertenece a un Cliente (puede ser null si es Venta Rápida/Consumidor Final)
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}