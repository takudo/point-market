# Point Market

## 動作確認

前提
- dockerがインストール済

手順（ローカル環境で動かす場合）

```shell
$ git clone git@github.com:takudo/point-market.git
$ cd point-market
$ cp backend/.env.example backend/.env
$ docker-compose up
$ docker-compose exec laravel composer install
$ docker-compose exec laravel php artisan key:generate
$ docker-compose exec laravel php artisan migrate
$ docker-compose exec laravel php artisan db:seed
[一度 docker-compose を止める]
$ docker-compose up #再起動
```

オプション: local以外の環境で動かしている場合（github codespacesなど）、 `backend/.env` ファイルの、 `L5_SWAGGER_CONST_HOST` を適切な値に編集する

### Swagger UI で動作確認する

- ブラウザで、以下にアクセスする

`http://localhost/api/documentation` （※上記コマンドをローカル端末で実施した場合のURL）

- Swagger UI 上で、 `User` の `POST /api/login` でログインする
  - email: `test-user@point-market.jp`
  - password: `password`

<img src="_README/swagger1.jpg" width="600px">
<img src="_README/swagger2.jpg" width="600px">

- ログイン後、Swagger UI にて、他の認証が必要なAPIも叩けるようになる

### 自動テスト

```shell
$ docker-compose exec laravel php artisan tests/Feature # Feature 配下のテストを実行
```

排他制御の関連で、特殊な自動テストをいくつか実装している。
必ず1ファイルずつ実行すること

```shell
$ docker-compose exec -e DB_DATABASE=testing laravel php artisan migrate:refresh
$ docker-compose exec laravel php artisan tests/FeatureSeparate/BuyingItemTransactionRollbackTest.php #購買処理の途中で落ちた場合の、ロールバックのテスト
```

```shell
$ docker-compose exec -e DB_DATABASE=testing laravel php artisan migrate:refresh
$ docker-compose exec laravel php artisan tests/FeatureSeparate/BuyingItemDeadlockTest.php # 実装済みの処理順と異なるが、プログラム的にデッドロックを起こして検知するテスト
```

```shell
$ docker-compose exec -e DB_DATABASE=testing laravel php artisan migrate:refresh
$ docker-compose exec laravel php artisan tests/FeatureSeparate/BuyingItemDeadlockAvoidanceTest.php # 実装済みの処理順で、デッドロックが起きないか・排他制御がうまくいっていることを確認するテスト
```

---

## 技術解説

### 商品購買時の排他制御

- 今回、購買時の排他制御は データベース（MySQL）側の機能を利用することにした
    - 同じ商品に対する購入のリクエストの程度は多くとも 10req/sec 程度であると仮定し、MySQL で排他制御を行う場合のロック対象を行単位とした場合、十分対処できる量であると考えた。
    - 一方、今回の処理対象は、システム内部で扱うデータのみだったためMySQLのトランザクションで対処可能と思われるが、実際には外部の決済サービスなどとのつなぎ込みによって、トランザクション処理はより複雑になることが想定される （参考記事: [Mercari マイクロサービスにおける決済トランザクション管理](https://engineering.mercari.com/blog/entry/2019-06-07-155849/)）

- MySQL の利用の前提に立つと、以下のようなデッドロックの発生が仮定できる
  - メモ: 以下の図のデッドロックをプログラム的に実装しているのが、 `backend/tests/FeatureSeparate/BuyingItemDeadlockTest.php` である

![デッドロック](./_README/deadlock1.png)

上記のイメージを実際に MySQL で操作すると以下のようにデッドロックが発生する

![デッドロック](./_README/deadlock2.jpg)

- この処理において、デッドロックの原因は排他ロックの取得順である。
  - 別データを相互にロックしようとしたときに、デッドロックになっている
  - そのため、ロックの取得順を統一する（= 必ず商品1のロックを最初に取得しようとする、など）形で回避が可能となる

以下が処理順を考慮してデッドロックを起きなくなるように修正したフローである（実際の処理は `ItemController` の `buyItem()` を参照）

![デッドロック](./_README/deadlock3.png)

また、この処理が意図通りになっているかどうかをテストしているのが `backend/tests/FeatureSeparate/BuyingItemDeadlockAvoidanceTest.php` である

#### 排他制御のテスト

- PHP において 並列処理・非同期を簡単に書くことが難しいため、特殊な実装を行っている
- 以下試したことを羅列する
  - Laravel の標準機能として Job という非同期処理がある
    - これは キューイングのためのDBがロックしてしまい、意図通りのテストを実装することができなかった
  - PHP Fiber という機能
    - これは[完全な非同期処理ではないらしく](https://tech-blog.rakus.co.jp/entry/20210915/php#Fibers) 処理順が意図通りになることが確認できなかった
  - `pcntl_fork` による子プロセス生成
    - 最終的にはこちらの処理に任せることとした。
- 参考記事
  - https://qiita.com/kuinaein/items/0b70e40947085560b34e
  - https://feeld-uni.com/?p=2463


### 認証

- [laravel sanctum の SPA認証](https://readouble.com/laravel/10.x/ja/sanctum.html#spa-authentication) を利用
  - FIXME [SPA認証を選んでいる理由を解説]

### OpenAPI 定義の出力（および、swagger ui）

- [swagger-php](https://zircote.github.io/swagger-php/guide/attributes.html) の Attribute で API の各ルートからOpen API 定義を出力するようにしている
- 出力されたファイルは、 `./api-docs` に吐き出される
- 同時に立ち上がる swagger-ui でも確認することができる
- Open API 定義 をコードに埋め込む考え方は [こちらの記事](https://zenn.dev/katzumi/articles/schema-driven-development-flow) を参考
- 別プロジェクトではこの定義を活用して、フロントエンド（SPA）側のAPIクライアントを自動生成するようにしていたりする。
- また、Open API 定義に則ったテストの自動化などの活用シーンもありそう。

### テスト

- Jest ライクに書ける Pest を利用

#### モックライブラリ(Mockery) の利用上の注意

- クラスメソッドのモッキングを実施している箇所があるが、これが影響範囲が大きいらしく、他のテストと一緒に混ぜて実行すると、他のテストが落ちる状況が発生した。
- `@runInSeparateProcess`,`@preserveGlobalState` のアノテーションで回避できるとのことだったが、手元ではうまく動作しないことと、こちらが[動いたとしても不安定](https://tsuyoshi-nakamura.hatenablog.com/entry/2017/11/15/190852)だという情報もあったため、一括実行とは別で単品で実行する形に切り出した。

### CI

- github actions で、PullRequest 時にテストの自動実行をするよう設定済み
  - [実行履歴](https://github.com/takudo/point-market/actions)
- 今回設定済みなのはテストの自動実行のみだが、ソースやdockerイメージのビルドをして本番環境へのデプロイまでやるケースが多い