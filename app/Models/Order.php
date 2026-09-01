<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'table_number',
        'customer_name',
        'total_price',
        'status',
        'payment_status',
        'payment_method',
    ];

public function items()
{
    return $this->hasMany(OrderItem::class, 'order_id');
}
}