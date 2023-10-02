<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return new UserResource(Auth::user());
        }

        return response()->json([], 401);
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
}
