<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'selling_price_point',
        'seller_user_id',
        'buyer_user_id',
    ];

    public static function getPublicItems() {
        return Item::where('status', 'on_sale')->get();
    }
}
