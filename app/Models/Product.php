<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Esta es la línea mágica que libera los campos (incluyendo el stock)
    protected $guarded = []; 

    // 1. Relación: Un producto pertenece a una categoría
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 2. Relación: Un producto tiene muchos lotes de inventario
    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }
}