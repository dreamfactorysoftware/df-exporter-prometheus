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

        // Bound metric-label cardinality. The raw URI contains record IDs
        // which would balloon the time-series count (one new series per
        // unique ID), exhausting Prometheus memory under any traffic that
        // hits /api/v2/<svc>/_table/<table>/<id>. Replace numeric and
        // UUID-like path segments with low-cardinality placeholders.
        $labelUri = self::normalizeUriForLabel($uri);

        $files = array_map(
            fn(UploadedFile $file) => $file->getRealPath(),
            iterator_to_array($request->files)
        );

        $apiKey = substr((string) $request->header('X-DreamFactory-API-Key'), 0, 8);

        $message = "{$method} {$uri} - Files: ".implode(', ', $files);

        try {
            $counter = $registry->getOrRegisterCounter(
                'dreamfactory',
                'api_requests_total',
                'The total amount of API requests processed',
                ['method', 'uri', 'api_key_short']
            );

            $counter->inc([$method, $labelUri, $apiKey]);

            $counter100 = $registry->getOrRegisterCounter(
                'dreamfactory',
                'api_requests_total_x100',
                'The total amount of API requests processed (x100)',
                ['method', 'uri', 'api_key_short']
            );

            $counter100->incBy(100, [$method, $labelUri, $apiKey]);

        } catch (MetricsRegistrationException $e) {
            Log::error($e->getMessage());
        }

        Log::info($message);
    }

    /**
     * Reduce a URL path to a low-cardinality form suitable for use as a
     * Prometheus label. Replaces numeric segments with `:id`, UUID-like
     * segments with `:uuid`, and truncates the result at 256 chars to
     * cap any other source of unbounded variance.
     */
    public static function normalizeUriForLabel(string $uri): string
    {
        $segments = explode('/', $uri);
        foreach ($segments as $i => $segment) {
            if ($segment === '') {
                continue;
            }
            if (ctype_digit($segment)) {
                $segments[$i] = ':id';
            } elseif (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $segment)) {
                $segments[$i] = ':uuid';
            } elseif (preg_match('/^[0-9a-f]{32,}$/i', $segment)) {
                $segments[$i] = ':hash';
            }
        }
        $normalized = implode('/', $segments);
        // Hard cap to prevent any other unbounded variance (e.g., admin
        // configuring weird routes that produce 10KB paths).
        if (strlen($normalized) > 256) {
            $normalized = substr($normalized, 0, 256) . '...';
        }
        return $normalized;
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

