<?php namespace Znck\Countries;

use Illuminate\Support\ServiceProvider;

class CountriesServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__).'/config/countries.php' => config_path('countries.php'),
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/countries.php', 'countries');
        $this->app->singleton('command.countries.update', UpdateCountriesCommand::class);
        $this->app->singleton(
            'translator.countries',
            function () {
                $locale = $this->app['config']['app.locale'];

                $loader = new FileLoader($this->app['files'], dirname(__DIR__).'/data');

                $trans = new Translator($loader, $locale);

                return $trans;
            }
        );
        $this->commands('command.countries.update');
    }

    public function provides()
    {
        return ['translator.countries', 'command.countries.update'];
    }
}
