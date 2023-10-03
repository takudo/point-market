<?php

use App\Models\Item;
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
    });

    describe('show', function(){
        test('指定された商品が販売中だった場合、結果が返ってくること', function () {
            $seller = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $seller->id])->refresh();

            $response = $this->get('/api/items/' . $item->id);

            expect($response->status())->toBe(200);
            expect($response['data']['id'])->toBe($item->id);
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

    });

});

