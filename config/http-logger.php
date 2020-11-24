<?php

return [

    /*
     * The log profile which determines whether a request should be logged.
     * It should implement `LogProfile`.
     */
    'log_profile' => DreamFactory\Core\DreamFactoryPrometheusExporter\Utility\HttpLogger\AllMethodsLogger::class,

    /*
     * The log writer used to write the request to a log.
     * It should implement `LogWriter`.
     */
    'log_writer' => DreamFactory\Core\DreamFactoryPrometheusExporter\Utility\HttpLogger\APIRequestExporter::class,

    /*
     * Filter out body fields which will never be logged.
     */
    'except' => [
        'password',
        'password_confirmation',
    ],

];
