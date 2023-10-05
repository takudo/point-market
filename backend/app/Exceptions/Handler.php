<?php

namespace App\Exceptions;

use App\Http\Resources\ErrorResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if(method_exists($e, 'getStatusCode')) {
                $statusCode = $e->getStatusCode();
                switch ($e->getStatusCode()) {
                    case 401:
                        $message = __('Unauthorized');
                        break;
                    case 403:
                        $message = __('Forbidden');
                        break;
                    case 404:
                        $message = __('Not Found');
                        break;
                    case 500:
                        $message = __('Internal Server Error');
                        break;
                    default:
                        return;
                }
            } else {
                $statusCode = 500;
                $message = __('Internal Server Error');
            }

            $errorMessage = [
                'message' => $message,
            ];

            return (new ErrorResource($errorMessage))->response()->setStatusCode($statusCode);
        });

        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage();

                if ($e->errors()) {
                    $message = Arr::flatten($e->errors())[0];
                };
                $errorMessage = [
                    'message' => $message,
                ];

                return (new ErrorResource($errorMessage))->response()->setStatusCode($e->status);
            };
        });
    }

    /**
     * refs: https://qiita.com/kat0/items/92e598b7ed50a55db616
     * @param $request
     * @param AuthenticationException $e
     * @return JsonResponse
     */
    protected function unauthenticated($request, AuthenticationException $e): JsonResponse
    {
        $errorMessage = [
            'message' => "Unauthorized",
        ];

        return (new ErrorResource($errorMessage))->response()->setStatusCode(401);
    }
}
