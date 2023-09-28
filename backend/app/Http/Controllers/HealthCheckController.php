<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\ResultStores\ResultStore;


/**
 * HealthCheckJsonResultsController よりコピー
 */
class HealthCheckController
{
    public function __invoke(Request $request, ResultStore $resultStore): Response
    {
        if ($request->has('fresh') || config('health.oh_dear_endpoint.always_send_fresh_results')) {
            Artisan::call(RunHealthChecksCommand::class);
        }

        $checkResults = $resultStore->latestResults();

        if($checkResults->allChecksOk()) {
            return response(['status' => 'OK'])
                ->header('Content-Type', 'application/json')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        } else {
            Log::warning($checkResults->toJson());
            return response(['status' => 'NG'], 500)
                ->header('Content-Type', 'application/json')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        }
    }
}
