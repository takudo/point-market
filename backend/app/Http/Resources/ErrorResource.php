<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

class ErrorResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }

    public static $wrap = null;
}
