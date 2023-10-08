<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

describe('UserController', function() {

    describe('login', function () {
        test('ログインできること', function () {
            $user = User::factory()->create();

            $response = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $this->assertAuthenticated();
            expect($response->status())->toBe(200);
        });

        test('パスワードが誤っている場合、ログインに失敗すること', function () {
            $user = User::factory()->create();

            $response = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            $this->assertGuest();
        });

    });

    describe('store', function () {
        test('メールアドレス以外の形式ではエラーになること', function () {

            $response = $this->postJson('/api/users/register', [
                'name' => 'hoge',
                'email' => 'incorrect-format-email',
                'password' => '!Q4QS$jirKf@QSEQ'
            ]);

            expect($response->status())->toBe(422);
            expect($response['message'])->toBe('The email field must be a valid email address.');
        });
        test('登録済みのメールアドレスではエラーになること', function () {

            $duplicateEmail = 'test@gmail.com';
            User::factory()->create(['email' => $duplicateEmail]);

            $response = $this->postJson('/api/users/register', [
                'name' => 'hoge',
                'email' => $duplicateEmail,
                'password' => '!Q4QS$jirKf@QSEQ'
            ]);

            expect($response->status())->toBe(422);
            expect($response['message'])->toBe('The email has already been taken.');
        });
        test('パスワードルールに即していないものはエラーになること', function () {

            $baseRequest = [
                'name' => 'hoge',
                'email' => 'test@gmail.com',
            ];

            // 小文字のみ
            $baseRequest['password'] = 'aaaaaaaaa';
            $response = $this->postJson('/api/users/register', $baseRequest);
            expect($response->status())->toBe(422);
            expect($response['message'])->toBe('The password field must contain at least one uppercase and one lowercase letter.');

            // 小文字と大文字
            $baseRequest['password'] = 'aaaaaaaaaA';
            $response = $this->postJson('/api/users/register', $baseRequest);
            expect($response->status())->toBe(422);
            expect($response['message'])->toBe('The password field must contain at least one symbol.');

            // 小文字、大文字、記号
            $baseRequest['password'] = 'aaaaaaaaaA!';
            $response = $this->postJson('/api/users/register', $baseRequest);
            expect($response->status())->toBe(422);
            expect($response['message'])->toBe('The password field must contain at least one number.');

            // 小文字、大文字、記号、数字で、簡単なパスワード
            $baseRequest['password'] = 'Password!1';
            $response = $this->postJson('/api/users/register', $baseRequest);
            expect($response->status())->toBe(422);
            expect($response['message'])->toBe('The given password has appeared in a data leak. Please choose a different password.');
        });

        test('登録の条件を満たす場合、登録できること', function () {
            $response = $this->postJson('/api/users/register', [
                'name' => 'hoge',
                'email' => 'test@gmail.com',
                'password' => '!Q4QS$jirKf@QSEQ'
            ]);

            expect($response->status())->toBe(204);
        });
    });

    describe('verifyEmail', function(){
        test('メールアドレス認証ができること。', function () {
            $user = User::factory()->create([
                'email_verified_at' => null,
                'points' => 0
            ]);

            Event::fake();

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $response = $this->actingAs($user)->get($verificationUrl);

            Event::assertDispatched(Verified::class);
            expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
            expect($response->status())->toBe(200);
            expect($response['message'])->toBe('Email verification is done.');

            // ポイントの付与もされていること
            expect($user['points'])->toBe(10000);
        });

        test('不正なハッシュ値の場合、メールアドレス認証に失敗すること', function () {
            $user = User::factory()->create([
                'email_verified_at' => null,
            ]);

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1('wrong-email')]
            );

            $this->actingAs($user)->get($verificationUrl);

            expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
        });
    });
});
