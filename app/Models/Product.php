<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // (Si tienes código previo aquí como $fillable o $guarded, déjalo intacto)

    // 1. Relación: Un producto pertenece a una categoría
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 2. Relación: Un producto tiene muchos lotes de inventario (lo agregamos de una vez)
    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }
}