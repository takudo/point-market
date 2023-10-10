<?php

namespace Tests\FeatureSeparate;

use App\Models\Item;
use App\Models\TradeHistory;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BuyingItemDeadlockAvoidanceTest extends TestCase
{

    public function test_buy_item_avoid_deadlock(): void
    {

        $pid1 = pcntl_fork();
        $pid2 = pcntl_fork();

        $itemId = 1;
        $sellerId = 10;
        $buyer1Id = 11;
        $buyer2Id = 12;

        if ($pid1 == -1 || $pid2 == -1) {
            die('fork できません');
        } else if ($pid1 && $pid2) {
            // 親プロセスの場合
            echo "Parent process, PID: " . posix_getpid() . PHP_EOL;

            try {
                $seller = User::factory()->create(['id' => $sellerId, 'points' => 2000])->refresh();
                $item = Item::factory()->create([
                    'id' => $itemId,
                    'status' => 'on_sale',
                    'seller_user_id' => $seller->id,
                    'selling_price_point' => 1000
                ])->refresh();
                $buyer1 = User::factory()->create(['id' => $buyer1Id, 'points' => 1000])->refresh();
                $buyer2 = User::factory()->create(['id' => $buyer2Id, 'points' => 1500])->refresh();
            } catch(Exception $exception){
                //なにもしない
                echo $exception;
            }

            pcntl_waitpid($pid1, $status);
            pcntl_waitpid($pid2, $status);

            // 子プロセスの結果を確認
            echo 'Parent assertion start.'.PHP_EOL;
            $seller->refresh();
            expect($seller->points)->toBe(3000); // Item分増えている
            $buyer1->refresh();
            expect($buyer1->points)->toBe(0); // Item分減っている
            $buyer2->refresh();
            expect($buyer2->points)->toBe(1500); // 買えなかったので、変わっていない

            $item->refresh();
            expect($item->status)->toBe('sold');
            expect($item->buyer_user_id)->toBe($buyer1->id); // buyer1が買えたことになっている

            $tradeHistory = TradeHistory::where('buyer_user_id', $buyer1->id)
                ->where('item_id', $item->id)
                ->where('seller_user_id', $seller->id)
            ;
            expect($tradeHistory)->not()->toBeEmpty();
            echo 'Parent assertion end.'.PHP_EOL;
            return;
        } else if ($pid1) {
            sleep(5);
            echo "process1 start." . PHP_EOL;

            $this->_buy_item('process1',$itemId, $buyer1Id);
            $msg = 'process1 end.';
            expect($msg)->toBe($msg);
            return;
        } else if ($pid2) {
            sleep(10);
            echo "process2 start." . PHP_EOL;

            $this->_buy_item('process2', $itemId, $buyer2Id);
            $msg = 'process2 end.';
            expect($msg)->toBe($msg);
            return;
        }

        // ダミーのアサーション
        expect(true)->toBeTrue();
    }

    private function _buy_item($processName, $itemId, $buyerId) {
        // ItemController::buyItem() とほぼ同様の処理。

        try{
            DB::beginTransaction();

            $item = Item::getPublicItems()->find($itemId);
            if(!$item) {
                DB::rollBack();
                return;
            }
            $item = Item::lockForUpdate()->find($item->id); //ロック取得

            $seller = User::lockForUpdate()->find($item->seller_user_id); //ロック取得
            if(!$seller) {
                DB::rollBack();
                return;
            }

            $buyer = User::lockForUpdate()->find($buyerId); //ロック取得
            if($seller->id == $buyer->id) {
                // 自分自身の商品は買えない
                DB::rollBack();
                return;
            }

            if($buyer->points < $item->selling_price_point) {
                // 保有ポイントよりも高い商品は買えない
                DB::rollBack();
                return;
            }

            if($item->buyer_user_id || $item->status == 'sold') {
                // すでに売約済みの商品。ロック待ちで発生しうる
                echo 'Already sold item:'. $item->id;
                DB::rollBack();
                return;
            }

            sleep(10);

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
        }
    }
}
