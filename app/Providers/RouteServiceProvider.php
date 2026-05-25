<?php

namespace Modules\ACP_Sales_Guide\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::moduleWeb('ACP_Sales_Guide', 'routes/web.php');
    }

    protected function mapApiRoutes(): void
    {
        Route::moduleApi('ACP_Sales_Guide', 'routes/api.php');
    }
}
