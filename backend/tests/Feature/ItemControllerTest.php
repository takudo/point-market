<?php

use App\Models\Item;
use App\Models\TradeHistory;
use App\Models\User;

describe('ItemController', function(){
    describe('index', function(){
        test('販売中の商品のみが結果で返ってくること', function () {

            $seller = User::factory()->create()->refresh();

            Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id]);
            Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id]);
            Item::factory()->create(['status' => 'not_on_sale', 'seller_user_id' => $seller->id]);
            Item::factory()->create(['status' => 'sold', 'seller_user_id' => $seller->id]);

            $response = $this->get('/api/items');

            expect($response->status())->toBe(200);
            expect($response['data'])->toHaveLength(2);
        });

        test('削除された商品は返ってこないこと', function(){
            $seller = User::factory()->create()->refresh();

            $deleteTargetItem = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id]);
            Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id]);
            Item::factory()->create(['status' => 'not_on_sale', 'seller_user_id' => $seller->id]);
            Item::factory()->create(['status' => 'sold', 'seller_user_id' => $seller->id]);
            $deleteTargetItem->delete();

            $response = $this->get('/api/items');
            expect($response->status())->toBe(200);
            expect($response['data'])->toHaveLength(1);
        });

    });

    describe('show', function(){
        test('指定された商品が販売中だった場合、結果が返ってくること', function () {
            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id])->refresh();

            $response = $this->get('/api/items/' . $item->id);

            expect($response->status())->toBe(200);
            expect($response['id'])->toBe($item->id);
        });

        test('指定された商品が存在しない場合、404が返ること', function () {
            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id])->refresh();

            $response = $this->get('/api/items/' . ($item->id + 1));
            expect($response->status())->toBe(404);
            expect($response['message'])->toBe('Not found item.');
        });

        test('指定された商品が存在するが、販売中ではない場合、404が返ること', function () {
            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'not_on_sale', 'seller_user_id' => $seller->id])->refresh();

            $response = $this->get('/api/items/' . ($item->id));
            expect($response->status())->toBe(404);
            expect($response['message'])->toBe('Not found item.');
        });

        test('削除された商品は返ってこないこと', function(){
            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id])->refresh();
            $item->delete();

            $response = $this->get('/api/items/' . $item->id);
            expect($response->status())->toBe(404);
        });

    });

    describe('buyItem', function(){
        test('商品のポイントが保有ポイントよりも大きかった場合、エラーになること', function () {

            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create([
                'status' => 'on_sale',
                'seller_user_id' => $seller->id,
                'selling_price_point' => 1001
            ])->refresh();

            $buyer = User::factory()->create(['points' => 1000])->refresh();

            $response = $this->actingAs($buyer)->post('/api/items/' . ($item->id) . '/buy');
            expect($response->status())->toBe(422);  //Unprocessable Entity
            expect($response['message'])->toBe('The points you have are not enough to buy the item.');
        });

        test('すでに売れている商品は購入できないこと（エラーになること）', function () {
            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create([
                'status' => 'sold',
                'seller_user_id' => $seller->id,
                'selling_price_point' => 1001
            ])->refresh();

            $buyer = User::factory()->create(['points' => 1000])->refresh();

            $response = $this->actingAs($buyer)->post('/api/items/' . ($item->id) . '/buy');
            expect($response->status())->toBe(404);
            expect($response['message'])->toBe('Not found item.');
        });

        test('未販売の商品は購入できないこと（エラーになること）', function () {
            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create([
                'status' => 'not_on_sale',
                'seller_user_id' => $seller->id,
                'selling_price_point' => 1001
            ])->refresh();
            $buyer = User::factory()->create(['points' => 1000])->refresh();

            $response = $this->actingAs($buyer)->post('/api/items/' . ($item->id) . '/buy');
            expect($response->status())->toBe(404);
            expect($response['message'])->toBe('Not found item.');
        });

        test('自分の商品は購入できないこと（エラーになること）', function () {
            $seller = User::factory()->create(['points' => 1000])->refresh();
            $item = Item::factory()->create([
                'status' => 'on_sale',
                'seller_user_id' => $seller->id,
                'selling_price_point' => 1000
            ])->refresh();
//            $buyer = User::factory()->create(['points' => 1000])->refresh();

            $response = $this->actingAs($seller)->post('/api/items/' . ($item->id) . '/buy');
            expect($response->status())->toBe(422);
            expect($response['message'])->toBe('You cannot buy items that you yourself are listing.');
        });

        test('条件を満たす場合、商品が購入できる', function () {
            $seller = User::factory()->create(['points' => 2000])->refresh();
            $item = Item::factory()->create([
                'status' => 'on_sale',
                'seller_user_id' => $seller->id,
                'selling_price_point' => 1000
            ])->refresh();
            $buyer = User::factory()->create(['points' => 1000])->refresh();

            $response = $this->actingAs($buyer)->post('/api/items/' . ($item->id) . '/buy');
            expect($response->status())->toBe(204);

            $item->refresh();
            expect($item->buyer_user_id)->toBe($buyer->id);
            expect($item->status)->toBe('sold');

            $buyer->refresh();
            expect($buyer->points)->toBe(0);

            $seller->refresh();
            expect($seller->points)->toBe(3000);

            $history = TradeHistory::where('item_id', $item->id)->first();
            expect($history->seller_user_id)->toBe($seller->id);
            expect($history->seller_point_result)->toBe(3000);
            expect($history->buyer_user_id)->toBe($buyer->id);
            expect($history->buyer_point_result)->toBe(0);
            expect($history->item_point)->toBe(1000);
        });
    });
});

