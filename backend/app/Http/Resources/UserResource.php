<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    properties: [
        new OA\Property('id', type: 'integer'),
        new OA\Property('name', type: 'string', description: '品名'),
        new OA\Property('points', type: 'integer', description: '保有ポイント'),
    ]
)]
class UserResource extends JsonResource
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
            'points' => $this->points,
        ];
    }
    public static $wrap = null;
}
