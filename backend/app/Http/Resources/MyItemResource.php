<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    properties: [
        new OA\Property('id', type: 'integer'),
        new OA\Property('name', type: 'string', description: '品名'),
        new OA\Property('description', type: 'string', description: '商品詳細'),
        new OA\Property('status', type: 'string', description: '状態'),
        new OA\Property('selling_price_point', type: 'integer', description: '販売価格（ポイント）'),
        new OA\Property('buyer_user_id', type: 'integer', description: '購入したユーザーのID'),
        new OA\Property('created_at', type: 'string', description: '登録日時'),
        new OA\Property('updated_at', type: 'string', description: '最終更新日時'),
    ]
)]
class MyItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'selling_price_point' => $this->selling_price_point,
            'buyer_user_id' => $this->buyer_user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    public static $wrap = null;
}
