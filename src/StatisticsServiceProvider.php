<?php namespace WhiteFrame\Statistics;

use Illuminate\Support\ServiceProvider;
use WhiteFrame\Support\Application;
use WhiteFrame\Support\Framework;

/**
 * Class StatisticsServiceProvider
 * @package WhiteFrame\Statistics
 */
class StatisticsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
        Framework::registerPackage('statistics');
    }

    public function boot()
    {
        $this->registerRepositoryMacros();
    }

    public function registerRepositoryMacros()
    {
        if(Framework::hasPackage('helloquent')) {
            app()->make('WhiteFrame\Statistics\Helloquent\RepositoryMacros')->register();
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}