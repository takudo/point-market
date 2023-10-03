<?php

namespace App\Http\Controllers;

use App\Http\Resources\MyItemCollection;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class MyItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/my/items',
        operationId: 'getMyItems',
        description: '自分の登録している商品の一覧を取得する',
        tags: ['MyItem'],
        responses: [
            new OA\Response(response: 200, description: 'AOK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property('data', type: 'array', items: new OA\Items(ref: 'App\Http\Resources\MyItemResource'))
                    ]
                )
            ),
        ]
    )]
    public function index()
    {
        $me = Auth::user();
        return new MyItemCollection(Item::where('seller_user_id', $me->id)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
