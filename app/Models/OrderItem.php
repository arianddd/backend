<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // 1. Relasi ke Model Product (Ini yang bikin error tadi)
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // 2. Relasi balik ke Model Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}