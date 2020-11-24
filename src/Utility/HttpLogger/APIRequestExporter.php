<?php

namespace DreamFactory\Core\DreamFactoryPrometheusExporter\Utility\HttpLogger;

use DreamFactory\Core\DreamFactoryPrometheusExporter\DreamFactoryCacheAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use Spatie\HttpLogger\LogWriter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Log\Logger;

class APIRequestExporter implements LogWriter
{
    public function logRequest(Request $request)
    {
        Log::error('asdasdasd');
        /**
         * @var CollectorRegistry $registry
         */
        $registry = new CollectorRegistry(new DreamFactoryCacheAdapter());
        $method = strtoupper($request->getMethod());

        $uri = preg_replace("/$", '', $request->getPathInfo(), 1);

        $files = array_map(function (UploadedFile $file) {
            return $file->getRealPath();
        }, iterator_to_array($request->files));

        $apiKey = substr($request->header('X-DreamFactory-API-Key'), 0, 8);

        $message = "{$method} {$uri} - Files: ".implode(', ', $files);

        try {
            $counter = $registry->getOrRegisterCounter('dreamfactory', 'api_requests_total', '', ['method', 'uri', 'api_key_short']);

            $counter->inc([$method, $uri, $apiKey]);

        } catch (MetricsRegistrationException $e) {
            Log::error($e->getMessage());
        }

        Log::info($message);
    }
}
