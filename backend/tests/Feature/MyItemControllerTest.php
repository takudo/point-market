<?php

use App\Models\Item;
use App\Models\User;
require_once '_commonTests.php';

describe('MyItemController', function(){
    describe('index', function(){

        test('未認証状態では 401が返ること', function () {
            $response = $this->get('/api/my/items');
            expect($response->status())->toBe(401);
        });

        test('email 未認証のユーザーでは 403が返ること', function () {
            emailUnverified($this,'get', '/api/my/items');
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

            foreach($response['data'] as $itemResponse) {
//                expect($item['seller_user_id'])->toBe($me->id);
                $item = Item::find($itemResponse['id']);
                expect($item['seller_user_id'])->toBe($me->id);
            }
        });

        test(' 削除された商品は返ってこないこと', function(){
            $me = User::factory()->create()->refresh();

            Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $me->id]);
            Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $me->id]);
            Item::factory()->create(['status' => 'not_on_sale', 'seller_user_id' => $me->id]);
            $deleteTargetItem = Item::factory()->create(['status' => 'sold', 'seller_user_id' => $me->id]);
            $deleteTargetItem->delete();

            $response = $this->actingAs($me)->get('/api/my/items');
            expect($response->status())->toBe(200);
            expect($response['data'])->toHaveLength(3);
        });
    });

    describe('store', function(){
        test('新規保存できること', function(){
            $me = User::factory()->create()->refresh();

            $response = $this->actingAs($me)
                ->postJson('/api/my/items', [
                    'name' => '商品名',
                    'status' => 'not_on_sale',
                    'description' => '商品の詳細な解説',
                    'selling_price_point' => 100,
                ]);

            expect($response->status())->toBe(201);
            expect($response['name'])->toBe('商品名');
            expect($response['status'])->toBe('not_on_sale');
            expect($response['description'])->toBe('商品の詳細な解説');
            expect($response['selling_price_point'])->toBe(100);
        });

        test('必須項目が無いとエラーになること', function(){
            $me = User::factory()->create()->refresh();
            $response = $this->actingAs($me)
                ->postJson('/api/my/items', [
//                    'name' => '商品名',
                    'status' => 'not_on_sale',
                    'selling_price_point' => 100,
                ]);
            expect($response->status())->toBe(422); //Unprocessable Entity
            expect($response['message'])->toBe('The name field is required.');

            $response = $this->actingAs($me)
                ->postJson('/api/my/items', [
                    'name' => '商品名',
//                    'status' => 'not_on_sale',
                    'selling_price_point' => 100,
                ]);
            expect($response->status())->toBe(422); //Unprocessable Entity
            expect($response['message'])->toBe('The status field is required.');

            $response = $this->actingAs($me)
                ->postJson('/api/my/items', [
                    'name' => '商品名',
                    'status' => 'not_on_sale',
//                    'selling_price_point' => 100,
                ]);
            expect($response->status())->toBe(422); //Unprocessable Entity
            expect($response['message'])->toBe('The selling price point field is required.');
        });

        test('ステータスに不正な文字列を入れるとエラーになること', function(){
            $me = User::factory()->create()->refresh();
            $response = $this->actingAs($me)
                ->postJson('/api/my/items', [
                    'name' => '商品名',
                    'status' => 'sold', // いきなり売れた状態は不正
                    'selling_price_point' => 100,
                ]);

            expect($response->status())->toBe(422); //Unprocessable Entity
            expect($response['message'])->toBe('The selected status is invalid.');

            $response = $this->actingAs($me)
                ->postJson('/api/my/items', [
                    'name' => '商品名',
                    'status' => 'invalid status', // 指定不可の値は不正
                    'selling_price_point' => 100,
                ]);
            expect($response->status())->toBe(422); //Unprocessable Entity
            expect($response['message'])->toBe('The selected status is invalid.');

        });

        test('売値（ポイント）に不正な値を入れるとエラーになること', function(){
            $me = User::factory()->create()->refresh();
            $response = $this->actingAs($me)
                ->postJson('/api/my/items', [
                    'name' => '商品名',
                    'status' => 'not_on_sale',
                    'selling_price_point' => -1,
                ]);

            expect($response->status())->toBe(422); //Unprocessable Entity
            expect($response['message'])->toBe('The selling price point field must be greater than or equal to 0.');
        });

    });
    describe('update', function(){
        test('指定した商品が更新できること', function(){
            $me = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $me->id])->refresh();

            $response = $this->actingAs($me)
                ->patchJson('/api/my/items/' . $item->id, [
                    'name' => $item->name . '_update',
                    'status' => 'not_on_sale',
                    'description' => $item->description . '_update',
                    'selling_price_point' => $item->selling_price_point + 100,
                ]);

            expect($response->status())->toBe(200);
            expect($response['name'])->toBe($item->name . '_update');
            expect($response['status'])->toBe('not_on_sale');
            expect($response['description'])->toBe($item->description . '_update');
            expect($response['selling_price_point'])->toBe($item->selling_price_point + 100);
        });

        test('自分が売り主（=seller_user_id）でない商品の更新はエラーになること', function(){
            $me = User::factory()->create()->refresh();
            $other = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $other->id])->refresh();

            $response = $this->actingAs($me)
                ->patchJson('/api/my/items/' . $item->id, [
                    'name' => $item->name . '_update',
                    'status' => 'not_on_sale',
                    'description' => $item->description . '_update',
                    'selling_price_point' => $item->selling_price_point + 100,
                ]);

            expect($response->status())->toBe(404);
        });

        test('売約済みの商品は、変更できないこと（エラーになること）', function(){
            $me = User::factory()->create()->refresh();
            $other = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'sold', 'seller_user_id' => $me->id, 'buyer_user_id' => $other->id])->refresh();

            $response = $this->actingAs($me)
                ->patchJson('/api/my/items/' . $item->id, [
                    'name' => $item->name . '_update',
                    'status' => 'not_on_sale',
                    'description' => $item->description . '_update',
                    'selling_price_point' => $item->selling_price_point + 100,
                ]);
            expect($response->status())->toBe(404);
        });
    });
    describe('destroy', function(){
        test('指定した商品が削除できること', function(){
            $me = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $me->id])->refresh();

            $response = $this->actingAs($me)
                ->delete('/api/my/items/' . $item->id, );

            expect($response->status())->toBe(204); // no content
            $item = Item::find($item->id);
            expect($item)->toBeNull(); // no content
        });

        test('自分が売り主（=seller_user_id）でない商品の削除はエラーになること', function(){
            $me = User::factory()->create()->refresh();
            $other = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'on_sale', 'seller_user_id' => $other->id])->refresh();

            $response = $this->actingAs($me)
                ->delete('/api/my/items/' . $item->id, );

            expect($response->status())->toBe(404);
        });

        test('売約済みの商品は、削除できないこと（エラーになること）', function(){
            $me = User::factory()->create()->refresh();
            $other = User::factory()->create()->refresh();
            $item = Item::factory()->create(['status' => 'sold', 'seller_user_id' => $me->id, 'buyer_user_id' => $other->id])->refresh();

            $response = $this->actingAs($me)
                ->delete('/api/my/items/' . $item->id,);
            expect($response->status())->toBe(404);
        });
    });

});

