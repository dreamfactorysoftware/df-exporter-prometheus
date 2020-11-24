<?php


namespace DreamFactory\Core\DreamFactoryPrometheusExporter\Utility\Prometheus;


use Illuminate\Support\Facades\Log;
use Prometheus\MetricFamilySamples;
use Prometheus\Storage\Adapter;

class DreamFactoryCacheAdapter implements Adapter
{

    const PROMETHEUS_METRIC_KEYS_SUFFIX = '_METRIC_KEYS';

    private static $prefix = 'PROMETHEUS_';

    /**
     * @return MetricFamilySamples[]
     */
    public function collect(): array
    {
        $metrics = [];
//        $metrics = array_merge($metrics, $this->collectHistograms());
//        $metrics = array_merge($metrics, $this->collectGauges());
        $metrics = array_merge($metrics, $this->collectCounters());
        return array_map(
            function (array $metric) {
                return new MetricFamilySamples($metric);
            },
            $metrics
        );
    }

    /**
     * @param mixed[] $data
     * @return void
     */
    public function updateHistogram(array $data): void
    {
        // TODO: Implement updateHistogram() method.
    }

    /**
     * @param mixed[] $data
     * @return void
     */
    public function updateGauge(array $data): void
    {
        // TODO: Implement updateGauge() method.
    }

    /**
     * @param mixed[] $data
     * @return void
     */
    public function updateCounter(array $data): void
    {
//        Cache::
        Log::error(json_encode($data));
    }

    /**
     * @return MetricFamilySamples[]
     */
    private function collectCounters()
    {
        return [];
    }

    /**
     * @param array $data
     * @return string
     */
    private function toMetricKey(array $data): string
    {
        return implode(':', [self::$prefix, $data['type'], $data['name']]);
    }
}
