<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Background Jobs
    |--------------------------------------------------------------------------
    |
    | This configuration defines which classes and methods are allowed to run
    | as background jobs. Use '*' to allow all methods of a class.
    |
    */
    'allowed_jobs' => [
        App\Jobs\ExampleJob::class => ['handle'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Retry Attempts
    |--------------------------------------------------------------------------
    |
    | The maximum number of times to retry a failed job.
    |
    */
    'max_retries' => 3,

    /*
    |--------------------------------------------------------------------------
    | Retry Delay
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait before retrying a failed job.
    |
    */
    'retry_delay' => 5,

    /*
    |--------------------------------------------------------------------------
    | Log Path
    |--------------------------------------------------------------------------
    |
    | Path to the log files for background jobs.
    |
    */
    'log_path' => storage_path('logs/background_jobs.log'),
    'error_log_path' => storage_path('logs/background_jobs_errors.log'),

    /*
    |--------------------------------------------------------------------------
    | Advanced Settings
    |--------------------------------------------------------------------------
    |
    | Additional settings for advanced features.
    |
    */
    'enable_priorities' => false,
    'enable_delays' => false,
    'enable_dashboard' => true,
];
