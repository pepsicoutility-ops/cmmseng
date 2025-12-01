<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CMMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the CMMS system including cost rates,
    | thresholds, and system behavior.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Labour Cost Rates
    |--------------------------------------------------------------------------
    |
    | Hourly rates for calculating labour costs in PM and WO execution.
    | Values are in IDR (Indonesian Rupiah) per hour.
    |
    */

    'labour_hourly_rate' => env('CMMS_LABOUR_HOURLY_RATE', 50000),

    /*
    |--------------------------------------------------------------------------
    | Downtime Cost Rate
    |--------------------------------------------------------------------------
    |
    | Cost per hour of equipment downtime for Work Orders.
    | Used in WO cost calculation.
    |
    */

    'downtime_cost_per_hour' => env('CMMS_DOWNTIME_COST_PER_HOUR', 100000),

    /*
    |--------------------------------------------------------------------------
    | PM Cost Overhead Percentage
    |--------------------------------------------------------------------------
    |
    | Overhead percentage applied to PM execution costs (labour + parts).
    | Default is 10% (0.1).
    |
    */

    'pm_overhead_percentage' => env('CMMS_PM_OVERHEAD_PERCENTAGE', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Stock Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Default stock levels for triggering alerts.
    |
    */

    'default_min_stock' => env('CMMS_DEFAULT_MIN_STOCK', 5),
    'default_max_stock' => env('CMMS_DEFAULT_MAX_STOCK', 100),

    /*
    |--------------------------------------------------------------------------
    | PM Compliance Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for PM compliance color coding.
    |
    */

    'compliance_excellent_threshold' => 95, // Green badge >= 95%
    'compliance_good_threshold' => 85,      // Yellow badge 85-94%
    // Red badge < 85%

    /*
    |--------------------------------------------------------------------------
    | Auto Number Formats
    |--------------------------------------------------------------------------
    |
    | Formats for auto-generated numbers.
    |
    */

    'wo_number_format' => 'WO-{year}{month}-{sequence}',
    'pm_code_format' => 'PM-{year}{month}-{sequence}',

    /*
    |--------------------------------------------------------------------------
    | AI/ML Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for ONNX machine learning models and OpenAI integration.
    |
    */

    'onnx_api_url' => env('ONNX_API_URL', 'http://localhost:5000/predict'),
    'onnx_timeout' => env('ONNX_TIMEOUT', 30), // seconds

    'openai_model' => env('OPENAI_MODEL', 'gpt-4'),
    'openai_temperature' => env('OPENAI_TEMPERATURE', 0.3),
    'openai_max_tokens' => env('OPENAI_MAX_TOKENS', 1000),

];
