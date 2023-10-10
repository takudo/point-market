<?php

namespace App\Http\Controllers;

use App\Http\Requests\MyItemSaveRequest;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\ItemCollection;
use App\Http\Resources\MyItemCollection;
use App\Http\Resources\MyItemResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class MyItemController extends Controller
{
    #[OA\Get(
        path: '/api/my/items',
        operationId: 'getMyItems',
        description: '自分の登録している商品の一覧を取得する',
        tags: ['MyItem'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'AOK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property('data', type: 'array', items: new OA\Items(ref: 'App\Http\Resources\MyItemResource'))
                    ]
                )
            ),
        ]
    )]
    public function index()
    {
        $me = Auth::user();
        return new MyItemCollection(Item::where('seller_user_id', $me->id)->paginate(100));
    }

    #[OA\Post(
        path: '/api/my/items',
        operationId: 'postMyItem',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: 'App\Http\Requests\MyItemSaveRequest')
        ),
        tags: ['MyItem'],
        responses: [
            new OA\Response(response: 200, description: 'AOK',
                content: new OA\JsonContent(ref: 'App\Http\Resources\MyItemResource')),
            new OA\Response(response: 401, description: 'Not allowed'),
        ]
    )]
    public function store(MyItemSaveRequest $request)
    {
        $me = Auth::user();
        $item = Item::create([
            'name' => $request->name,
            'description' => $request->description,
            'selling_price_point' => $request->selling_price_point,
            'status' => $request->status,
            'seller_user_id' => $me->id,
        ]);

        return new MyItemResource($item);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    #[OA\Patch(
        path: '/api/my/items/{item_id}',
        operationId: 'patchMyItem',
        description: '自分が登録した商品の更新',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: 'App\Http\Requests\MyItemSaveRequest')
        ),
        tags: ['MyItem'],
        parameters: [
            new OA\Parameter(name: 'item_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'AOK',
                content: new OA\JsonContent(ref: 'App\Http\Resources\MyItemResource')
            ),
        ]
    )]
    public function update(Request $request, int $id)
    {
        $me = Auth::user();
        $item = Item::find($id);

        if(!$item or $item->seller_user_id != $me->id) {
            return (new ErrorResource(['message' => 'Not found Item.']))->response()->setStatusCode(404);
        }
        if($item->status == 'sold' || $item->buyer_user_id) {
            return (new ErrorResource(['message' => 'Not found Item.']))->response()->setStatusCode(404);
        }

        $item->name = $request->name;
        $item->description = $request->description;
        $item->selling_price_point = $request->selling_price_point;
        $item->status = $request->status;

        $item->save();

        return new MyItemResource($item);
    }


    #[OA\Delete(
        path: '/api/my/items/{item_id}',
        operationId: 'deleteMyItem',
        description: '自分が登録した商品の削除',
        tags: ['MyItem'],
        parameters: [
            new OA\Parameter(name: 'item_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'No content',),
        ]
    )]
    public function destroy(int $id)
    {
        $me = Auth::user();
        $item = Item::find($id);

        if(!$item or $item->seller_user_id != $me->id) {
            return (new ErrorResource(['message' => 'Not found Item.']))->response()->setStatusCode(404);
        }
        if($item->status == 'sold' || $item->buyer_user_id) {
            return (new ErrorResource(['message' => 'Not found Item.']))->response()->setStatusCode(404);
        }

        $item->delete();

        return response()->noContent();
    }
}
