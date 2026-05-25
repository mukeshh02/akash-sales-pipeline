<?php

namespace Modules\AkashSalesPipeline\Providers;

use Modules\Core\Facades\Innoclapps;
use Modules\Core\Menu\MenuItem;
use Modules\Core\Support\ModuleServiceProvider;

class AkashSalesPipelineServiceProvider extends ModuleServiceProvider
{
    protected bool $withViews = true;

    protected bool $withMigrations = true;

    /**
     * Register the RouteServiceProvider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Configure the module after the application has booted.
     */
    protected function setup(): void
    {
        $this->registerPermissions();

        \Modules\Core\Facades\Innoclapps::whenReadyForServing(function () {
            $dealResource = \Modules\Core\Facades\Innoclapps::resourceByName('deals');
            if ($dealResource) {
                $panel = \Modules\Core\Pages\Panel::make('akash-sales-guide', 'AkashSalesGuide')
                    ->heading('Akash Camera Sales Guide');
                $panel->order = 5;
                
                $dealResource->getDetailPage()->panel($panel);
            }
        });
    }

    /**
     * Register the sidebar menu item.
     * Visibility is controlled by the 'view akash sales pipeline' permission.
     */
    protected function menu(): array
    {
        return [
            MenuItem::make(
                __('akashsalespipeline::akashsalespipeline.name'),
                '/akash-sales-pipeline'
            )
                ->icon('ChartSquareBar')
                ->position(50)
                ->canSeeWhen('view akash sales pipeline'),

            MenuItem::make(
                "Today's Follow-ups",
                '/akash-sales-pipeline/today'
            )
                ->icon('Bell')
                ->position(51)
                ->canSeeWhen('view akash sales pipeline'),

            MenuItem::make(
                'Sales Content Setup',
                '/akash-sales-pipeline/sales-content'
            )
                ->icon('DocumentText')
                ->position(52)
                ->canSeeWhen('view akash sales pipeline'),
        ];
    }

    /**
     * Register module permissions with the Core permission system.
     */
    protected function registerPermissions(): void
    {
        Innoclapps::permissions(function ($manager) {
            $manager->group(
                ['name' => 'akashsalespipeline', 'as' => 'Akash Sales Pipeline'],
                function ($manager) {
                    $manager->view('view', [
                        'as'          => 'View',
                        'permissions' => [
                            'view akash sales pipeline' => __('akashsalespipeline::akashsalespipeline.view'),
                        ],
                    ]);
                }
            );
        });
    }

    protected function moduleName(): string
    {
        return 'AkashSalesPipeline';
    }

    protected function moduleNameLower(): string
    {
        return 'akashsalespipeline';
    }
}
