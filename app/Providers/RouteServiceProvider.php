<?php

namespace Modules\AkashSalesPipeline\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define the routes for the module.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Register web routes. Wrapped with the 'web' middleware group by moduleWeb().
     */
    protected function mapWebRoutes(): void
    {
        Route::moduleWeb('AkashSalesPipeline', 'routes/web.php');
    }

    /**
     * Register API routes. Wrapped with the API prefix and 'api' middleware by moduleApi().
     */
    protected function mapApiRoutes(): void
    {
        Route::moduleApi('AkashSalesPipeline', 'routes/api.php');
    }
}
