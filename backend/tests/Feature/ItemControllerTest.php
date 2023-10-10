<?php

use App\Models\Item;
use App\Models\TradeHistory;
use App\Models\User;
require_once '_commonTests.php';

describe('ItemController', function(){
    describe('index', function(){

        function _setupItems() {
            $seller = User::factory()->create()->refresh();
            $item1 = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id])->refresh();
            $item2 = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id])->refresh();
            $item3 = Item::factory()->create(['status' => 'not_on_sale', 'seller_user_id' => $seller->id])->refresh();
            $item4 = Item::factory()->create(['status' => 'sold', 'seller_user_id' => $seller->id])->refresh();

            return ['seller' => $seller, 'items' => [$item1, $item2, $item3, $item4]];
        }

        test('販売中の商品のみが結果で返ってくること', function () {
            $items = _setupItems();
            $response = $this->get('/api/items');

            expect($response->status())->toBe(200);
            expect($response['data'])->toHaveLength(2);
        });

        test('削除された商品は返ってこないこと', function(){
            $items = _setupItems();
            $deleteTargetItem = $items['items'][0];

            $deleteTargetItem->delete();

            $response = $this->get('/api/items');
            expect($response->status())->toBe(200);
            expect($response['data'])->toHaveLength(1);
        });

    });

    describe('show', function(){

        function _setupItem($status = 'on_sale'){
            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => $status, 'seller_user_id' => $seller->id])->refresh();
            return ['seller' => $seller, 'item' => $item];
        }

        test('指定された商品が販売中だった場合、結果が返ってくること', function () {
            $itemSet = _setupItem();
            $response = $this->get('/api/items/' . $itemSet['item']->id);

            expect($response->status())->toBe(200);
            expect($response['id'])->toBe($itemSet['item']->id);
        });

        test('指定された商品が存在しない場合、404が返ること', function () {
            $itemSet = _setupItem();
            $response = $this->get('/api/items/' . ($itemSet['item']->id + 1));

            expect($response->status())->toBe(404);
            expect($response['message'])->toBe('Not found item.');
        });

        test('指定された商品が存在するが、販売中ではない場合、404が返ること', function () {
            $itemSet = _setupItem('not_on_sale');
            $response = $this->get('/api/items/' . $itemSet['item']->id);

            expect($response->status())->toBe(404);
            expect($response['message'])->toBe('Not found item.');
        });

        test('削除された商品は返ってこないこと', function(){
            $itemSet = _setupItem('not_on_sale');
            $itemSet['item']->delete();

            $response = $this->get('/api/items/' . $itemSet['item']->id);
            expect($response->status())->toBe(404);
        });

    });

    describe('buyItem', function(){

        function _setupForBuyItem($itemStatus = 'on_sale', $itemPricePoint = 1000, $buyerPoint = 1000, $sellerPoint = 1000) {
            $seller = User::factory()->create(['points' => $sellerPoint])->refresh();
            $item = Item::factory()->create([
                'status' => $itemStatus,
                'seller_user_id' => $seller->id,
                'selling_price_point' => $itemPricePoint
            ])->refresh();
            $buyer = User::factory()->create(['points' => $buyerPoint])->refresh();

            return ['seller' => $seller, 'item' => $item, 'buyer' => $buyer];
        }

        test('email 未認証のユーザーでは 403が返ること', function () {
            $itemSet = _setupForbuyItem();
            emailUnverified($this,'post', '/api/items/' . ($itemSet['item']->id) . '/buy');
        });

        test('商品のポイントが保有ポイントよりも大きかった場合、エラーになること', function () {
            $itemSet = _setupForbuyItem(itemPricePoint: 1001, buyerPoint: 1000);

            $response = $this->actingAs($itemSet['buyer'])->post('/api/items/' . ($itemSet['item']->id) . '/buy');
            expect($response->status())->toBe(422);  //Unprocessable Entity
            expect($response['message'])->toBe('The points you have are not enough to buy the item.');
        });

        test('すでに売れている商品は購入できないこと（エラーになること）', function () {
            $itemSet = _setupForBuyItem(itemStatus: 'sold');

            $response = $this->actingAs($itemSet['buyer'])->post('/api/items/' . ($itemSet['item']->id) . '/buy');
            expect($response->status())->toBe(404);
            expect($response['message'])->toBe('Not found item.');
        });

        test('未販売の商品は購入できないこと（エラーになること）', function () {
            $itemSet = _setupForBuyItem(itemStatus: 'not_on_sale');

            $response = $this->actingAs($itemSet['buyer'])->post('/api/items/' . ($itemSet['item']->id) . '/buy');
            expect($response->status())->toBe(404);
            expect($response['message'])->toBe('Not found item.');
        });

        test('自分の商品は購入できないこと（エラーになること）', function () {
            $itemSet = _setupForBuyItem();

            $response = $this->actingAs($itemSet['seller'])->post('/api/items/' . ($itemSet['item']->id) . '/buy');
            expect($response->status())->toBe(422);
            expect($response['message'])->toBe('You cannot buy items that you yourself are listing.');
        });

        test('条件を満たす場合、商品が購入できる', function () {
            $itemSet = _setupForBuyItem();

            $seller = $itemSet['seller'];
            $item = $itemSet['item'];
            $buyer = $itemSet['buyer'];

            $response = $this->actingAs($itemSet['buyer'])->post('/api/items/' . ($item->id) . '/buy');
            expect($response->status())->toBe(204);

            $item->refresh();
            expect($item->buyer_user_id)->toBe($buyer->id);
            expect($item->status)->toBe('sold');

            $buyer->refresh();
            expect($buyer->points)->toBe(0);

            $seller->refresh();
            expect($seller->points)->toBe(2000);

            $history = TradeHistory::where('item_id', $item->id)->first();
            expect($history->seller_user_id)->toBe($seller->id);
            expect($history->seller_point_result)->toBe(2000);
            expect($history->buyer_user_id)->toBe($buyer->id);
            expect($history->buyer_point_result)->toBe(0);
            expect($history->item_point)->toBe(1000);
        });

    });
});

