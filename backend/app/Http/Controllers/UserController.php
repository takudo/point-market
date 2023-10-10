<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailVerificationRequest;
use App\Http\Requests\UserRegisterPostRequest;
use App\Http\Resources\UserRegisterResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use http\Env\Response;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Post(
        path: '/api/login',
        operationId: 'postUserLogin',
        tags: ['User'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: 'App\Http\Requests\UserLoginPostRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'AOK',
                content: new OA\JsonContent(ref: 'App\Http\Resources\UserResource')),
            new OA\Response(response: 401, description: 'Not allowed'),
        ]
    )]
    public function login(Request $request)
    {
        $credentials = ['email' => $request->email, 'password' => $request->password];

        if (Auth::attempt($credentials) && $user = Auth::user()) {
            if(!$user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Your email address is not verified.'], 403);
            }
            $request->session()->regenerate();
            return new UserResource($user);
        }

        return response()->json(['message' => 'Email and/or password are incorrect.'], 401);
    }

    #[OA\Get(
        path: '/api/users/me',
        operationId: 'getMe',
        tags: ['User'],
        responses: [
            new OA\Response(response: 200, description: 'AOK',
                content: new OA\JsonContent(ref: 'App\Http\Resources\UserResource'
            )),
            new OA\Response(response: 401, description: 'Not allowed'),
        ]
    )]
    public function me(Request $request)
    {
        if(Auth::hasUser()){
            return new UserResource(Auth::user());
        }

        return response()->json([], 401);
    }


    #[OA\Post(
        path: '/api/users/register',
        operationId: 'postUserRegister',
        tags: ['User'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: 'App\Http\Requests\UserRegisterPostRequest')
        ),
        responses: [
            new OA\Response(response: 204, description: 'No content',),
            new OA\Response(response: 401, description: 'Not allowed'),
        ]
    )]
    public function store(UserRegisterPostRequest $request) {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'points' => 0
        ]);

        event(new Registered($user));
        Auth::login($user);
        return response()->noContent();
    }


    public function verifyEmail(EmailVerificationRequest $request)
    {
        $user = User::find($request->id);

        if ($user->hasVerifiedEmail()) {
            return response(['message' => 'Already confirmed Email.'], 422);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $user->points = 10000;
            $user->save();
        }
        return response(['message' => 'Email verification is done.'], 200);
    }
}
