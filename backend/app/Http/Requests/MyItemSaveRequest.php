<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use OpenApi\Attributes as OA;

#[OA\Schema(
    required: ['name', 'selling_price_point', 'status'],
    properties: [
        new OA\Property('name', type: 'string'),
        new OA\Property('description', type: 'string'),
        new OA\Property('status', type: 'string', enum: ['not_on_sale', 'on_sale']),
        new OA\Property('selling_price_point', type: 'integer'),
    ]
)]
class MyItemSaveRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'status' => ['required', new Enum(SaveRequestStatus::class)],
            'selling_price_point' => 'required|integer|gte:0',
        ];
    }
}

enum SaveRequestStatus: string {
    case NotOnSale = 'not_on_sale';
    case OnSale = 'on_sale';
}
