## Installation (Laravel 5.x)
Run:

    composer require webkid/bugreporter

Add the service provider to `config/app.php` under `providers`:

    'providers' => [
        Webkid\BugReporter\BugReporterServiceProvider::class,
    ]

Publish Config

	php artisan vendor:publish --provider="Webkid\BugReporter\BugReporterServiceProvider"

Update config file:

	config/bugreports.php
	
Update app/Exceptions/Handler.php file:
	
	public function report(Exception $e)
	{
		//fire bug report here
		$bugReport = new Reporter();
		$bugReport->sendReport($e);
		//old code
		return parent::report($e);
	}
	
Also provide environment variables:
	UKIE_REPORTS_ENABLE=true
	UKIE_REPORTS_URL=
	UKIE_REPORTS_TOKEN=
