<?php

namespace DreamFactory\Core\DreamFactoryPrometheusExporter\Utility\HttpLogger;

use Illuminate\Http\Request;
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
        $registry = CollectorRegistry::getDefault();
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
