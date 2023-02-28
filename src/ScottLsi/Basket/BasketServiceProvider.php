<?php namespace ScottLsi\Basket;

use Illuminate\Support\ServiceProvider;

class BasketServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Boot the service provider.
	 */
	public function boot()
	{
		if (function_exists('config_path')) {
			$this->publishes([
				__DIR__.'/config/config.php' => config_path('shopping_basket.php'),
			], 'config');
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__.'/config/config.php', 'shopping_basket');

		$this->app->singleton('basket', function($app)
		{
            $storageClass = config('shopping_basket.storage');
            $eventsClass = config('shopping_basket.events');

            $storage = $storageClass ? new $storageClass() : $app['session'];
            $events = $eventsClass ? new $eventsClass() : $app['events'];
			$instanceName = 'basket';

            // default session or basket identifier. This will be overridden when calling Basket::session($sessionKey)->add() etc..
            // like when adding a basket for a specific user name. Session Key can be string or maybe a unique identifier to bind a basket
            // to a specific user, this can also be a user ID
			$session_key = '4yTlTDKu3oJOfzD';

			return new Basket(
				$storage,
				$events,
				$instanceName,
				$session_key,
				config('shopping_basket')
			);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}
}
