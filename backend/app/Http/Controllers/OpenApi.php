<?php
namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    name: 'XSRF-TOKEN',
    type: "apiKey",
    in: 'cookie',
    securityScheme: 'X-XSRF-TOKEN',
)]

#[OA\OpenApi(
    info: new OA\Info(title: "PointMarket Backend API", version: "0.1",),
    servers: [
        new OA\Server(url: "http://localhost")
    ],
)]
class OpenApi {}
