<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemCollection;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use OpenApi\Attributes as OA;

class ItemController extends Controller
{
    #[OA\Get(
        path: '/api/items',
        operationId: 'getItems',
        description: '現在販売中（= status が on_sale）の商品の一覧を取得する',
        tags: ['Item'],
        responses: [
            new OA\Response(response: 200, description: 'AOK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property('data', type: 'array', items: new OA\Items(ref: 'App\Http\Resources\ItemResource'))
                    ]
                )
            ),
        ]
    )]
    public function index()
    {
        return new ItemCollection(Item::getPublicItems());
    }

    #[OA\Get(
        path: '/api/items/{item_id}',
        operationId: 'getItemById',
        description: '現在販売中（= status が on_sale）の商品で指定されたIDの商品を取得する',
        tags: ['Item'],
        parameters: [
            new OA\Parameter(name: 'item_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'AOK',
                content: new OA\JsonContent(ref: 'App\Http\Resources\ItemResource')
            ),
        ]
    )]
    public function show($id)
    {
        $item = Item::getPublicItems()->find($id);
        if($item) {
            return new ItemResource($item);
        }
        return response('notfound', 404);
    }
}
