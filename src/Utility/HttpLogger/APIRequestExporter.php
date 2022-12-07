<?php

namespace DreamFactory\Core\DreamFactoryPrometheusExporter\Utility\HttpLogger;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Spatie\HttpLogger\LogWriter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class APIRequestExporter implements LogWriter
{
    public function logRequest(Request $request)
    {
        /**
         * @var CollectorRegistry $registry
         */
        $predisAdapter = new PredisAdapter(Cache::getStore()->getRedis()->connection('cache')->client());
        $registry = new CollectorRegistry($predisAdapter);
        $method = strtoupper($request->getMethod());

        $uri = $request->getPathInfo();
        if (! $this->isNeedToLog($uri)) {
            return;
        }

        $uri = preg_replace("/\/$/", '', $request->getPathInfo(), 1);

        $files = array_map(
            fn(UploadedFile $file) => $file->getRealPath(), 
            iterator_to_array($request->files)
        );

        $apiKey = substr($request->header('X-DreamFactory-API-Key'), 0, 8);

        $message = "{$method} {$uri} - Files: ".implode(', ', $files);

        try {
            $counter = $registry->getOrRegisterCounter(
                'dreamfactory',
                'api_requests_total',
                'The total amount of API requests processed',
                ['method', 'uri', 'api_key_short']
            );

            $counter->inc([$method, $uri, $apiKey]);

            $counter100 = $registry->getOrRegisterCounter(
                'dreamfactory',
                'api_requests_total_x100',
                'The total amount of API requests processed (x100)',
                ['method', 'uri', 'api_key_short']
            );

            $counter100->incBy(100, [$method, $uri, $apiKey]);

        } catch (MetricsRegistrationException $e) {
            Log::error($e->getMessage());
        }

        Log::info($message);
    }

    private function isNeedToLog($uri) {
        if (env('PROMETHEUS_INCLUDE_SYSTEM_REQUESTS')) {
            return true;
        } else if (preg_match("/^\/api\/v2\/system\//", $uri)
            || preg_match("/^\/api\/v2$/", $uri)
            || preg_match("/^\/api\/v2\/user\/.*/", $uri)
            || preg_match("/^\/api\/v2\/api_docs\/*/", $uri)) {
            return false;
        }
        return true;
    }
}

