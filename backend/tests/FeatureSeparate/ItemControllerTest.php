<?php

namespace Tests\FeatureSeparate;

use App\Models\Item;
use App\Models\User;
use Mockery;
use Tests\TestCase;

//describe('ItemController', function(){
//
//    describe('buyItem', function(){
//        test('途中でエラーが起きた場合、書き込まれたデータはロールバックする', function(){
//
//        });
//    });
//});

class ItemControllerTest extends TestCase
{
    public function test_buy_item_transaction_rollback(): void
    {
        // TradeHistory を モッキング
        Mockery::mock('alias:App\Models\TradeHistory')
            ->shouldReceive('create')
            ->andThrow('Exception');

        $seller = User::factory()->create(['points' => 2000])->refresh();
        $item = Item::factory()->create([
            'status' => 'on_sale',
            'seller_user_id' => $seller->id,
            'selling_price_point' => 1000
        ])->refresh();
        $buyer = User::factory()->create(['points' => 1000])->refresh();

        $response = $this->actingAs($buyer)->post('/api/items/' . ($item->id) . '/buy');
        expect($response->status())->toBe(500);

        $seller->refresh();
        expect($seller->points)->toBe(2000);
        $buyer->refresh();
        expect($buyer->points)->toBe(1000);
        $item->refresh();
        expect($item->status)->toBe('on_sale');
        expect($item->buyer_user_id)->toBeNull();

    }
}
