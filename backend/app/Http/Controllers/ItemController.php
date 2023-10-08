<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemCollection;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\TradeHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        return response(['message' => 'Not found item.'], 404);
    }


    #[OA\Post(
        path: '/api/items/{item_id}/buy',
        operationId: 'buyItemById',
        description: '現在販売中（= status が on_sale）の商品で指定されたIDの商品を、自身の保有するポイントで購入する',
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
    public function buyItem($id) {

        try{
            DB::beginTransaction();

            $item = Item::getPublicItems()->find($id);
            if(!$item) {
                DB::rollBack();
                return response(['message' => 'Not found item.'], 404);
            }
            $item = Item::lockForUpdate()->find($item->id); //ロック取得

            $seller = User::lockForUpdate()->find($item->seller_user_id); //ロック取得
            if(!$seller) {
                DB::rollBack();
                return response(['message' => 'Not found Seller user.'], 404);
            }

            $buyer = Auth::user();
            $buyer = User::lockForUpdate()->find($buyer->id); //ロック取得
            if($seller->id == $buyer->id) {
                // 自分自身の商品は買えない
                DB::rollBack();
                return response(['message' => 'You cannot buy items that you yourself are listing.'], 422);
            }

            if($buyer->points < $item->selling_price_point) {
                // 保有ポイントよりも高い商品は買えない
                DB::rollBack();
                return response(['message' => 'The points you have are not enough to buy the item.'], 422);
            }

            if($item->buyer_user_id || $item->status == 'sold') {
                // すでに売約済みの商品。ロック待ちで発生しうる
                DB::rollBack();
                return response(['message' => 'Inconsistency will occur in the data. Please contact the administrator.'], 500);
            }

            // 1. 商品を売約済みにする
            $item->status = 'sold';
            $item->buyer_user_id = $buyer->id;
            $item->save();

            // 2. 売り手にポイントを足す
            $seller->points = $seller->points + $item->selling_price_point;
            $seller->save();

            // 3. 買い手から販売価格を引く
            $buyer->points = $buyer->points - $item->selling_price_point;
            $buyer->save();

            // 4. 購入履歴を登録する
            TradeHistory::create([
                'seller_user_id' => $seller->id,
                'seller_point_result' => $seller->points,
                'buyer_user_id' => $buyer->id,
                'buyer_point_result' => $buyer->points,
                'item_id' => $item->id,
                'item_point' => $item->selling_price_point,
            ]);
            DB::commit();
        } catch (\Exception $exception) {
            Log::error($exception);
            Log::error('エラーが発生したため、ロールバックを実施');
            DB::rollBack();
            return response(['message' => 'An unknown error has occurred. Please try the same operation again or contact the administrator.'], 500);
        }

        return response()->noContent();
    }
}
