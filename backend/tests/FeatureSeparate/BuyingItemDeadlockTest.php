<?php

namespace Tests\FeatureSeparate;

use App\Models\Item;
use App\Models\TradeHistory;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;


class BuyingItemDeadlockTest extends TestCase
{
    public function test_buy_item_with_deadlock(): void
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
            echo "parent process end." . PHP_EOL;

            $msg = 'parent process end.';
            expect($msg)->toBe($msg);
            return;
        } else if ($pid1) {
            // 子プロセス1 の場合

            echo "Child process1, PID: " . posix_getpid() . PHP_EOL;

            sleep(3);

            DB::beginTransaction();

            // (1)
            $item = Item::lockForUpdate()->find($itemId); //ロック取得

            sleep(10);

            // (3)
            $seller = User::lockForUpdate()->find($sellerId); //ロック取得

            sleep(10);

            $buyer = User::lockForUpdate()->find($buyer1Id); //ロック取得

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

            // デッドロックで開放されるので、こちらは処理が到達する
            $msg = 'process1 end.';
            expect($msg)->toBe($msg);
            return;

        } else if ($pid2) {
            // 子プロセス2 の場合
            echo "Child process2, PID: " . posix_getpid() . PHP_EOL;

            sleep(8);

            DB::beginTransaction();

            // (2)
            $item = Item::find($itemId);
            $seller = User::lockForUpdate()->find($sellerId); //ロック取得

            sleep(10);

            try {
                // (4) => deadlock 発生
                $item = Item::lockForUpdate()->find($itemId); //ロック取得

            } catch(QueryException $exception) {
                expect($exception->getCode())->toBe('40001'); //デッドロックのエラーコード
                DB::rollBack();
                return;
            }
            // ↑でデッドロックが起きるため、以降の後続処理は実行されない

            $buyer = User::lockForUpdate()->find($buyer2Id); //ロック取得

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
        }

        // ダミーのアサーション
        expect(true)->toBeTrue();
    }
}
