<?php

/**
 * ACP Sales Guide — Configuration
 *
 * All CRM model class names are configurable here so the plugin
 * works with any Concord CRM installation regardless of namespace changes.
 *
 * Override in your .env:
 *   ACP_DEAL_MODEL=Modules\Deals\Models\Deal
 */

return [

    /*
    |--------------------------------------------------------------------------
    | CRM Model Bindings (No hard dependencies — fully configurable)
    |--------------------------------------------------------------------------
    | If your Concord CRM installation uses different model namespaces,
    | override these via environment variables.
    */
    'deal_model'     => env('ACP_DEAL_MODEL',     'Modules\\Deals\\Models\\Deal'),
    'stage_model'    => env('ACP_STAGE_MODEL',    'Modules\\Deals\\Models\\Stage'),
    'pipeline_model' => env('ACP_PIPELINE_MODEL', 'Modules\\Deals\\Models\\Pipeline'),
    'user_model'     => env('ACP_USER_MODEL',     'Modules\\Users\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Panel Settings
    |--------------------------------------------------------------------------
    */
    'panel_heading'  => env('ACP_PANEL_HEADING', 'Sales Guide'),
    'panel_order'    => 5,

    /*
    |--------------------------------------------------------------------------
    | Permission Key
    |--------------------------------------------------------------------------
    */
    'permission'     => 'view acp sales guide',

];
