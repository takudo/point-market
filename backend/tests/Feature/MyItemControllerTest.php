<?php

use App\Models\Item;
use App\Models\User;

describe('MyItemController', function(){
    describe('index', function(){

        test('未認証状態では 401が返ること', function () {
            $response = $this->get('/api/my/items');

            expect($response->status())->toBe(401);
        });

        test('自分の登録した商品のみが返ってくること', function () {

            $me = User::factory()->create()->refresh();

            Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $me->id]);
            Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $me->id]);
            Item::factory()->create(['status' => 'not_on_sale', 'seller_user_id' => $me->id]);
            Item::factory()->create(['status' => 'sold', 'seller_user_id' => $me->id]);

            $other = User::factory()->create()->refresh();
            Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $other->id]);
            Item::factory()->create(['status' => 'not_on_sale', 'seller_user_id' => $other->id]);
            Item::factory()->create(['status' => 'sold', 'seller_user_id' => $other->id]);

            $response = $this->actingAs($me)->get('/api/my/items');

            expect($response->status())->toBe(200);
            expect($response['data'])->toHaveLength(4);

            foreach($response['data'] as $item) {
                expect($item['seller_user_id'])->toBe($me->id);
            }
        });
    });

});

