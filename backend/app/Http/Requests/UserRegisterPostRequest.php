<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

#[OA\Schema(
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property('name', type: 'string'),
        new OA\Property('email', type: 'string'),
        new OA\Property('password', type: 'string'),
    ]
)]
class UserRegisterPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|unique:users|email:rfc',
            'password' => ['required',
                Password::min(8)   // 最低 8文字
                    ->mixedCase()       // 大文字小文字
                    ->numbers()         // 数字
                    ->symbols()         // 記号
                    ->uncompromised()   // 簡単なものでないか
            ],
        ];
    }
}
