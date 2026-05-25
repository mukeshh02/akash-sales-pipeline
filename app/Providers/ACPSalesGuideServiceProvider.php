<?php

namespace Modules\ACP_Sales_Guide\Providers;

use Modules\Core\Facades\Innoclapps;
use Modules\Core\Menu\MenuItem;
use Modules\Core\Support\ModuleServiceProvider;

class ACPSalesGuideServiceProvider extends ModuleServiceProvider
{
    protected bool $withViews      = true;
    protected bool $withMigrations = true;

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Merge module config
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'acp_sales_guide');
    }

    /**
     * Configure the module after the application has booted.
     * All CRM-specific calls are wrapped in try/catch so the plugin
     * never crashes the CRM if a core API changes between versions.
     */
    protected function setup(): void
    {
        $this->registerPermissions();

        Innoclapps::whenReadyForServing(function () {
            try {
                $dealResource = Innoclapps::resourceByName('deals');

                if ($dealResource) {
                    $panel = \Modules\Core\Pages\Panel::make('acp-sales-guide', 'ACPSalesGuide')
                        ->heading(config('acp_sales_guide.panel_heading', 'Sales Guide'));

                    $panel->order = config('acp_sales_guide.panel_order', 5);
                    $dealResource->getDetailPage()->panel($panel);
                }
            } catch (\Throwable $e) {
                // Graceful degradation — panel not injected if CRM API is unavailable
                logger()->warning('[ACP_Sales_Guide] Panel registration failed: ' . $e->getMessage());
            }
        });
    }

    protected function menu(): array
    {
        $perm = config('acp_sales_guide.permission', 'view acp sales guide');

        return [
            MenuItem::make(__('acpsalesguide::acpsalesguide.name'), '/acp-sales-guide')
                ->icon('ChartSquareBar')
                ->position(50)
                ->canSeeWhen($perm),

            MenuItem::make("Today's Follow-ups", '/acp-sales-guide/today')
                ->icon('Bell')
                ->position(51)
                ->canSeeWhen($perm),

            MenuItem::make('Sales Content Setup', '/acp-sales-guide/sales-content')
                ->icon('DocumentText')
                ->position(52)
                ->canSeeWhen($perm),
        ];
    }

    protected function registerPermissions(): void
    {
        Innoclapps::permissions(function ($manager) {
            $manager->group(
                ['name' => 'acpsalesguide', 'as' => 'ACP Sales Guide'],
                function ($manager) {
                    $manager->view('view', [
                        'as'          => 'View',
                        'permissions' => [
                            'view acp sales guide' => __('acpsalesguide::acpsalesguide.view'),
                        ],
                    ]);
                }
            );
        });
    }

    protected function moduleName(): string      { return 'ACP_Sales_Guide'; }
    protected function moduleNameLower(): string { return 'acpsalesguide'; }
}
