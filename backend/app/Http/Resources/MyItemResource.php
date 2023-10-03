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
            'seller_user_id' => $this->seller_user_id,
            'buyer_user_id' => $this->buyer_user_id,
        ];
    }
}
