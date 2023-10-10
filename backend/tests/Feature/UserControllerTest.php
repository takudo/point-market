<?php

use App\Models\User;
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

        test('メールアドレス未認証状態のときには、ログインに失敗すること', function () {

            $email = 'hogetest@gmail.com';
            $password = '!Q4QS$jirKf@QSEQ';

            $response = $this->postJson('/api/users/register', [
                'name' => 'hoge',
                'email' => $email,
                'password' => $password
            ]);

            $response = $this->postJson('/api/login', [
                'email' => $email,
                'password' => $password,
            ]);

            expect($response->status())->toBe(403);
        });


    });

    describe('store', function () {

        function _testUserRegister($context, $email = 'test@gmail.com', $password = '!Q4QS$jirKf@QSEQ', $expectedStatus = 200, $expectedMessage = 'OK'){
            $response = $context->postJson('/api/users/register', [
                'name' => 'hoge',
                'email' => $email,
                'password' => $password
            ]);

            expect($response->status())->toBe($expectedStatus);
            expect($response['message'])->toBe($expectedMessage);
        };

        test('メールアドレス以外の形式ではエラーになること', function () {
            _testUserRegister(
                context: $this,
                email: 'incorrect-format-email',
                expectedStatus: 422,
                expectedMessage: 'The email field must be a valid email address.'
            );
        });
        test('登録済みのメールアドレスではエラーになること', function () {
            $duplicateEmail = 'test@gmail.com';
            User::factory()->create(['email' => $duplicateEmail]);

            _testUserRegister( context: $this,
                email: $duplicateEmail,
                expectedStatus: 422,
                expectedMessage: 'The email has already been taken.'
            );
        });

        test('パスワードルールに即していない（小文字のみ）場合はエラーになること', function(){
            _testUserRegister( context: $this,
                password: 'abcdefghijklmo',
                expectedStatus: 422,
                expectedMessage: 'The password field must contain at least one uppercase and one lowercase letter.'
            );
        });
        test('パスワードルールに即していない（小文字・大文字）場合はエラーになること', function(){
            _testUserRegister( context: $this,
                password: 'abcdefghijklmoABC',
                expectedStatus: 422,
                expectedMessage: 'The password field must contain at least one symbol.'
            );
        });
        test('パスワードルールに即していない（小文字・大文字・記号）場合はエラーになること', function(){
            _testUserRegister( context: $this,
                password: 'abcdefghijklmoABC!',
                expectedStatus: 422,
                expectedMessage: 'The password field must contain at least one number.'
            );
        });
        test('パスワードルールに即していない（小文字、大文字、記号、数字で、簡単なパスワード）場合はエラーになること', function(){
            _testUserRegister( context: $this,
                password: 'Password!1',
                expectedStatus: 422,
                expectedMessage: 'The given password has appeared in a data leak. Please choose a different password.'
            );
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
