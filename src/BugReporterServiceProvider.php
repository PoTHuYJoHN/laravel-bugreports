<?php

namespace Webkid\BugReporter;

use Illuminate\Support\ServiceProvider;

class BugReporterServiceProvider extends ServiceProvider
{
	protected $defer = false;
	
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/config/config.php' => config_path('bugreports.php'),
		]);
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('bugreporter', function($app)
		{
			return new Reporter();
		});

		$this->mergeConfigFrom(
			__DIR__ . '/config/config.php', 'bugreports'
		);
	}
}
