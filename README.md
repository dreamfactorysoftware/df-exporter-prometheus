# DreamFactory Exporter for Prometheus 

Adds export metrics about using the DreamFactory API. 

**Warning**. This package is not compatible with [df-mongo-logs](https://github.com/dreamfactorysoftware/df-mongo-logs).

## Configuration

This package depends on the Redis cache. DreamFactory supports InMemory and Redis cache, 
but DreamFactory Exporter will only work if `CACHE_DRIVER` is `redis`.

Configurations for this package:

| Env                                | Description                                                    | Values          | Default value | 
|------------------------------------|----------------------------------------------------------------|-----------------|---------------|
| CACHE_DRIVER                       | It should always be `redis`                                    | any             | `redis`       |
| PROMETHEUS_ENABLED                 | Determines whether it is allowed to collect and expose metrics | `true`, `false` | `false`       |
| PROMETHEUS_TELEMETRY               | Metrics endpoint for Prometheus                                | string          | `/metrics`    |
| PROMETHEUS_INCLUDE_SYSTEM_REQUESTS | Do Prometheus have to log system calls?                        | `true`, `false` | `false`       |
| PROMETHEUS_ALLOWED_HOSTNAME        | Allowed hostname to call /metrics endpoint                     | `/regex/`       | `/^.*$/`      |

## Metrics

```text
# HELP dreamfactory_api_requests_total The total amount of API requests processed
# TYPE dreamfactory_api_requests_total counter
# HELP php_info Information about the PHP environment.
# TYPE php_info gauge
```
