<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeHistory extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'seller_user_id',
        'seller_point_result',
        'buyer_user_id',
        'buyer_point_result',
        'item_id',
        'item_point',
    ];
}
