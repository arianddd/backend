<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model
{
    protected $fillable = ['name', 'category', 'price', 'description', 'image'];
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];
}
