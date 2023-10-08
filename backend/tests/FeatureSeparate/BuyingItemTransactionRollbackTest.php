<?php

namespace Tests\FeatureSeparate;

use App\Models\Item;
use App\Models\User;
use Mockery;
use Tests\TestCase;

class BuyingItemTransactionRollbackTest extends TestCase
{
    public function test_buy_item_transaction_rollback(): void
    {
        // TradeHistory を モッキング
        // -> TradeHistory の Insert がトランザクション中の途中で実施されるため、そこで無理やりエラーを発生させる
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
